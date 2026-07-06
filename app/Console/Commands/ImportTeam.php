<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ImportTeamBackupService;
use Illuminate\Console\Command;

class ImportTeam extends Command
{
    protected $signature = 'team:import {path : Local path to a backup zip} {--name= : New team name} {--owner= : Importer/owner user id}';

    protected $description = 'Import a backup zip from another environment into a new team.';

    public function handle(ImportTeamBackupService $service): int
    {
        $path = (string) $this->argument('path');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $owner = $this->option('owner') !== null
            ? User::find((int) $this->option('owner'))
            : User::query()->orderBy('id')->first();

        if (! $owner) {
            $this->error('No owner user resolved — pass --owner.');

            return self::FAILURE;
        }

        $team = $service->import($path, $this->option('name') ?: null, $owner);

        $this->info("Imported into new team {$team->id} ({$team->name}), owned by user {$owner->id}.");

        return self::SUCCESS;
    }
}
