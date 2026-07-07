<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamCloneService;
use Illuminate\Console\Command;

class CloneTeam extends Command
{
    protected $signature = 'team:clone {source : Source team id} {--name= : New team name} {--owner= : Owner user id}';

    protected $description = 'Clone a team\'s configuration (template) into a new team.';

    public function handle(TeamCloneService $service): int
    {
        $source = Team::withoutGlobalScope('archived')->find((int) $this->argument('source'));
        if (! $source) {
            $this->error("Team {$this->argument('source')} not found.");

            return self::FAILURE;
        }

        $ownerId = $this->option('owner') !== null ? (int) $this->option('owner') : (int) $source->user_id;
        $owner = User::find($ownerId);
        if (! $owner) {
            $this->error("Owner user {$ownerId} not found.");

            return self::FAILURE;
        }

        $name = $this->option('name') ?: "Copy of {$source->name}";
        $new = $service->clone($source, $name, $owner);

        $this->info("Cloned team {$source->id} -> new team {$new->id} ({$new->name}).");

        return self::SUCCESS;
    }
}
