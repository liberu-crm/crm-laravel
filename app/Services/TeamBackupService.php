<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Support\TenantModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Builds a JSON snapshot zip of every row a team owns: one {Model}.json per
 * team-scoped model (discovered via TenantModels) plus team-owned extras, and
 * a manifest. Reads UNSCOPED by design — a backup must capture all of a team's
 * rows regardless of request/tenant context — and is guarded by the
 * completeness + "only this team's rows" tests.
 */
class TeamBackupService
{
    private const FORMAT_VERSION = 1;

    /** Team-owned tables that are not IsTenantModel models. */
    private const EXTRA_TABLES = ['team_user', 'team_invitations', 'team_subscriptions'];

    /**
     * @return array{disk: string, path: string, size: int}
     */
    public function backup(Team $team, ?string $disk = null): array
    {
        $disk ??= (string) config('filesystems.default', 'local');

        $tmp = tempnam(sys_get_temp_dir(), 'teambackup');
        if ($tmp === false) {
            throw new RuntimeException('Could not allocate a temp file for the backup.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the backup archive for writing.');
        }

        $manifest = [
            'format_version' => self::FORMAT_VERSION,
            'team' => ['id' => $team->id, 'name' => $team->name],
            'generated_at' => now()->toIso8601String(),
            'models' => [],
            'extras' => [],
        ];

        // ponytail: loads each model's rows into memory to serialize; fine for
        // realistic team sizes. If a team ever holds millions of rows, stream
        // chunkById to NDJSON temp files and addFile instead.
        //
        // Raw DB rows (not Eloquent ->toJson()) so values are DB-native and the
        // restore is a verbatim DB::table()->insert() — no cast round-trip
        // ambiguity (json columns, dates). Keyed by model basename; restore
        // resolves basename -> table via the same TenantModels enumeration.
        foreach (TenantModels::all() as $class) {
            $table = (new $class)->getTable();

            // A tenant-scoped model with no table is dead/pending schema drift
            // (e.g. SiteSettings) — it holds no rows, so skip rather than fatal.
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->where('team_id', $team->id)->get();
            $name = class_basename($class);
            $zip->addFromString("models/{$name}.json", (string) $rows->toJson(JSON_PRETTY_PRINT));
            $manifest['models'][$name] = $rows->count();
        }

        // The team root row.
        $zip->addFromString('extras/teams.json', (string) DB::table('teams')->where('id', $team->id)->get()->toJson());
        $manifest['extras']['teams'] = 1;

        foreach (self::EXTRA_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)->where('team_id', $team->id)->get();
            $zip->addFromString("extras/{$table}.json", (string) $rows->toJson());
            $manifest['extras'][$table] = $rows->count();
        }

        $zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->close();

        $path = 'backups/team-'.$team->id.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';
        Storage::disk($disk)->put($path, (string) file_get_contents($tmp));
        @unlink($tmp);

        return [
            'disk' => $disk,
            'path' => $path,
            'size' => (int) Storage::disk($disk)->size($path),
        ];
    }
}
