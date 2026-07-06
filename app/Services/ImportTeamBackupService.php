<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TeamImportException;
use App\Models\Team;
use App\Models\User;
use App\Support\SchemaGraph;
use App\Support\TenantModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Imports a backup zip taken on a DIFFERENT database into a brand-new team.
 * Every row gets a fresh PK; cross-model foreign keys are remapped via the
 * introspected schema graph; user references resolve to the importing admin
 * (source-env user ids are meaningless here). Not to be confused with restore
 * (#477), which is same-team, original-PK recovery.
 */
class ImportTeamBackupService
{
    private const FORMAT_VERSION = 1;

    /** Columns that reference users but may lack a declared FK constraint. */
    private const USER_REF_COLUMNS = ['assigned_to', 'created_by', 'updated_by', 'user_id', 'owner_id'];

    public function import(string $localZipPath, ?string $name, User $importer): Team
    {
        $zip = new ZipArchive;
        if ($zip->open($localZipPath) !== true) {
            throw new TeamImportException('Could not open the backup archive.');
        }

        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (! is_array($manifest) || ($manifest['format_version'] ?? null) !== self::FORMAT_VERSION) {
                throw new TeamImportException('Unsupported or unreadable backup format.');
            }

            $teamName = $name ?: (string) ($manifest['team']['name'] ?? 'Imported team');

            // FK checks OFF must wrap (not nest inside) the transaction — sqlite
            // ignores PRAGMA foreign_keys once a transaction is open. The team is
            // created INSIDE the transaction so a failed import (e.g. a unique
            // collision on the target env) rolls it back — no orphan team.
            return Schema::withoutForeignKeyConstraints(
                fn (): Team => DB::transaction(function () use ($zip, $teamName, $importer): Team {
                    $team = new Team;
                    $team->name = $teamName;
                    $team->user_id = $importer->id;
                    $team->personal_team = false;
                    $team->save();

                    $idMap = $this->insertAll($zip, $team);
                    $this->remapForeignKeys($team, $idMap, $importer->id);

                    return $team;
                }),
            );
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<string, array<int, int>> table => (old id => new id)
     */
    private function insertAll(ZipArchive $zip, Team $team): array
    {
        $idMap = [];

        foreach (TenantModels::all() as $class) {
            $table = (new $class)->getTable();
            if (! Schema::hasTable($table)) {
                continue;
            }

            $json = $zip->getFromName('models/'.class_basename($class).'.json');
            if ($json === false) {
                continue;
            }

            /** @var list<array<string, mixed>> $rows */
            $rows = json_decode((string) $json, true) ?: [];
            $map = [];
            foreach ($rows as $row) {
                $oldId = (int) $row['id'];
                unset($row['id']);
                $row['team_id'] = $team->id;
                $map[$oldId] = (int) DB::table($table)->insertGetId($row);
            }

            if ($map !== []) {
                $idMap[$table] = $map;
            }
        }

        return $idMap;
    }

    /**
     * @param  array<string, array<int, int>>  $idMap
     */
    private function remapForeignKeys(Team $team, array $idMap, int $importerId): void
    {
        $edges = SchemaGraph::edges(array_keys($idMap));

        foreach (array_keys($idMap) as $table) {
            $patchable = $this->patchableColumns($table, $edges[$table] ?? []);
            if ($patchable === []) {
                continue;
            }

            foreach (DB::table($table)->where('team_id', $team->id)->get() as $row) {
                $updates = [];
                foreach ($patchable as $column => $referenced) {
                    $old = $row->{$column} ?? null;
                    if ($old === null) {
                        continue;
                    }

                    if ($referenced === 'users') {
                        $updates[$column] = $importerId;
                    } elseif (isset($idMap[$referenced])) {
                        // Reference into another imported table; unmapped -> null.
                        $updates[$column] = $idMap[$referenced][(int) $old] ?? null;
                    }
                    // Reference to a non-imported, non-user table: left as-is.
                }

                if ($updates !== []) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    /**
     * Declared FK columns (authoritative referenced table) plus name-heuristic
     * user columns that have no declared FK. team_id is never remapped.
     *
     * @param  array<string, string>  $fkEdges  column => referenced table
     * @return array<string, string> column => referenced table
     */
    private function patchableColumns(string $table, array $fkEdges): array
    {
        $patchable = [];

        foreach ($fkEdges as $column => $referenced) {
            if ($column === 'team_id') {
                continue;
            }
            $patchable[$column] = $referenced;
        }

        foreach (self::USER_REF_COLUMNS as $column) {
            if (! isset($patchable[$column]) && Schema::hasColumn($table, $column)) {
                $patchable[$column] = 'users';
            }
        }

        // Undeclared FKs: many `xxx_id` columns have no DB constraint. Guess the
        // referenced table by Laravel convention (contact_id -> contacts). The
        // remap only fires when that table was actually imported, so a wrong
        // guess (e.g. google_event_id) is harmless — it stays as-is.
        foreach (Schema::getColumnListing($table) as $column) {
            if (isset($patchable[$column]) || $column === 'team_id' || $column === 'id') {
                continue;
            }
            if (str_ends_with($column, '_id')) {
                $patchable[$column] = Str::plural(substr($column, 0, -3));
            }
        }

        return $patchable;
    }
}
