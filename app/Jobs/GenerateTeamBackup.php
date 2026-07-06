<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Team;
use App\Models\TeamBackup;
use App\Services\TeamBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Builds a team's backup off the request cycle and records the outcome on the
 * TeamBackup row. Reads unscoped (not TenantAware) by design; loads the team
 * past the 'archived' scope so an archived team can still be backed up before
 * a hard delete.
 */
class GenerateTeamBackup implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $backupId) {}

    public function handle(TeamBackupService $service): void
    {
        $backup = TeamBackup::findOrFail($this->backupId);
        $team = Team::withoutGlobalScope('archived')->findOrFail($backup->team_id);

        $backup->update(['status' => 'processing']);

        try {
            $result = $service->backup($team, $backup->disk);
            $backup->update([
                'status' => 'completed',
                'path' => $result['path'],
                'size_bytes' => $result['size'],
            ]);
        } catch (Throwable $e) {
            $backup->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
