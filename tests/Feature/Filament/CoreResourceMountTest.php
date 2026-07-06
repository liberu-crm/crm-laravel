<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\ActivationResource;
use App\Filament\App\Resources\CompanyResource;
use App\Filament\App\Resources\LeadResource;
use App\Filament\App\Resources\NoteResource;
use App\Filament\App\Resources\OpportunityResource;
use App\Filament\App\Resources\TaskResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CoreResourceMountTest extends TestCase
{
    use RefreshDatabase;

    private function actingManagerWithTeam(): Team
    {
        Role::findOrCreate('manager', 'web');
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();
        $user->assignRole('manager');
        $this->actingAs($user);

        return $team;
    }

    private function assertResourceMounts(string $resource): void
    {
        $team = $this->actingManagerWithTeam();
        $url = '/app/'.$team->id.'/'.$resource::getSlug();
        $this->get($url)->assertStatus(200);
    }

    public function test_company_index_mounts(): void
    {
        $this->assertResourceMounts(CompanyResource::class);
    }

    public function test_lead_index_mounts(): void
    {
        $this->assertResourceMounts(LeadResource::class);
    }

    public function test_opportunity_index_mounts(): void
    {
        $this->assertResourceMounts(OpportunityResource::class);
    }

    public function test_note_index_mounts(): void
    {
        $this->assertResourceMounts(NoteResource::class);
    }

    public function test_task_index_mounts(): void
    {
        $this->assertResourceMounts(TaskResource::class);
    }

    public function test_activation_index_mounts(): void
    {
        $this->assertResourceMounts(ActivationResource::class);
    }
}
