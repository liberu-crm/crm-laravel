<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TeamNotEmptyException;
use App\Exceptions\TeamRestoreException;
use App\Models\Team;
use App\Models\TeamBackup;
use App\Support\TenantModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Same-team disaster recovery: re-inserts a team's rows from one of its own
 * completed backups, with original PKs, so foreign keys stay valid without any
 * id remapping. Refuses to run against a team that still holds data (would
 * duplicate rows / collide on PKs). Atomic — a single bad row rolls back the
 * whole restore. Reads unscoped by design.
 */
class TeamRestoreService
{
    private const FORMAT_VERSION = 1;

    /**
     * @return array<string, int> rows restored per model
     */
    public function restore(TeamBackup $backup): array
    {
        if ($backup->status !== 'completed' || $backup->path === null) {
            throw new TeamRestoreException("Backup #{$backup->id} is not a completed backup.");
        }

        $disk = Storage::disk($backup->disk);
        if (! $disk->exists($backup->path)) {
            throw new TeamRestoreException("Backup file is missing: {$backup->path}.");
        }

        // ZipArchive needs a local path — stage the artifact to a temp file.
        $tmp = tempnam(sys_get_temp_dir(), 'teamrestore');
        if ($tmp === false) {
            throw new TeamRestoreException('Could not allocate a temp file for the restore.');
        }
        file_put_contents($tmp, (string) $disk->get($backup->path));

        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            throw new TeamRestoreException('Could not open the backup archive.');
        }

        try {
            $team = $this->resolveTargetTeam($zip);
            $this->assertTeamEmpty($team);

            // FK checks OFF must wrap (not nest inside) the transaction: on
            // sqlite `PRAGMA foreign_keys` is a no-op once a transaction is
            // open, so toggle it before BEGIN. With FKs off, insert order is
            // irrelevant; the inner transaction keeps the restore atomic.
            $restored = [];
            Schema::withoutForeignKeyConstraints(function () use ($zip, $team, &$restored): void {
                $restored = DB::transaction(fn (): array => $this->insertRows($zip, $team));
            });

            return $restored;
        } finally {
            $zip->close();
            @unlink($tmp);
        }
    }

    private function resolveTargetTeam(ZipArchive $zip): Team
    {
        $manifestJson = $zip->getFromName('manifest.json');
        if ($manifestJson === false) {
            throw new TeamRestoreException('Backup archive has no manifest.');
        }

        $manifest = json_decode($manifestJson, true);
        if (! is_array($manifest) || ($manifest['format_version'] ?? null) !== self::FORMAT_VERSION) {
            throw new TeamRestoreException('Unsupported or unreadable backup format.');
        }

        $team = Team::withoutGlobalScope('archived')->find($manifest['team']['id'] ?? 0);
        if (! $team) {
            throw new TeamRestoreException('Target team no longer exists.');
        }

        return $team;
    }

    /**
     * The first team-scoped table already holding data for the team, or null
     * when the team is empty. Public so a trigger can give instant "not empty"
     * feedback before queueing a restore.
     */
    public function firstPopulatedTable(Team $team): ?string
    {
        foreach (TenantModels::all() as $class) {
            $table = (new $class)->getTable();
            if (Schema::hasTable($table) && DB::table($table)->where('team_id', $team->id)->exists()) {
                return $table;
            }
        }

        return null;
    }

    private function assertTeamEmpty(Team $team): void
    {
        if ($table = $this->firstPopulatedTable($team)) {
            throw new TeamNotEmptyException(
                "Team {$team->id} already has data (`{$table}`); refusing to restore over it."
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function insertRows(ZipArchive $zip, Team $team): array
    {
        $restored = [];

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
            // Trust nothing: only rows actually belonging to this team.
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => (int) ($row['team_id'] ?? 0) === $team->id,
            ));

            if ($rows === []) {
                continue;
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
            $restored[class_basename($class)] = count($rows);
        }

        return $restored;
    }
}
