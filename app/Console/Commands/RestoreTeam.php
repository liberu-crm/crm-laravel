<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RestoreTeamBackup;
use App\Models\TeamBackup;
use Illuminate\Console\Command;

class RestoreTeam extends Command
{
    protected $signature = 'team:restore {backup : The team_backups id}';

    protected $description = 'Restore a team\'s data from one of its completed backups (same-team recovery).';

    public function handle(): int
    {
        $id = (int) $this->argument('backup');
        $backup = TeamBackup::find($id);

        if (! $backup) {
            $this->error("Backup {$id} not found.");

            return self::FAILURE;
        }

        RestoreTeamBackup::dispatch($backup->id);

        $this->info("Queued restore from backup #{$backup->id} into team {$backup->team_id}.");

        return self::SUCCESS;
    }
}
