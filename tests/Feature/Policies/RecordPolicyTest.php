<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Deal/Lead/Task/Opportunity policies authorize a record when it belongs to the
 * user's current team ($model->belongsToTeam($user->currentTeam?->id)) — the
 * same idiom the controllers use. currentTeam resolves via the real
 * current_team_id column (there is no team_id column on users).
 */
class RecordPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function recordModels(): array
    {
        return [
            'Deal' => [Deal::class],
            'Lead' => [Lead::class],
            'Task' => [Task::class],
            'Opportunity' => [Opportunity::class],
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    #[DataProvider('recordModels')]
    public function test_same_team_user_may_view_update_delete(string $modelClass): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->current_team_id = $team->id;

        $record = $modelClass::factory()->create();
        $record->team_id = $team->id;

        $this->assertTrue($user->can('view', $record));
        $this->assertTrue($user->can('update', $record));
        $this->assertTrue($user->can('delete', $record));
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    #[DataProvider('recordModels')]
    public function test_other_team_user_is_denied(string $modelClass): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        $user = User::factory()->create();
        $user->current_team_id = $otherTeam->id;

        $record = $modelClass::factory()->create();
        $record->team_id = $team->id;

        $this->assertFalse($user->can('view', $record));
        $this->assertFalse($user->can('update', $record));
        $this->assertFalse($user->can('delete', $record));
    }
}
