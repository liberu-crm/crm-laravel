<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\App\Resources\TeamRoleLogResource;
use App\Filament\App\Resources\TeamRoleLogResource\Pages\ListTeamRoleLogs;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamRoleAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $this->admin->currentTeam;
        setPermissionsTeamId($this->team->id);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    public function test_changing_a_role_records_an_audit_entry(): void
    {
        $member = User::factory()->create();
        $this->team->users()->attach($member);

        app(TeamManagementService::class)->changeTeamRole($member, $this->team, Role::Manager);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.role_changed',
            'auditable_type' => User::class,
            'auditable_id' => $member->id,
            'team_id' => $this->team->id,
            'user_id' => $this->admin->id,
        ]);
    }

    private function audit(string $action, int $teamId): AuditLog
    {
        $log = new AuditLog([
            'action' => $action,
            'description' => 'x',
            'user_id' => $this->admin->id,
            'ip_address' => '0.0.0.0',
        ]);
        $log->setAttribute('team_id', $teamId);
        $log->save();

        return $log;
    }

    public function test_resource_shows_only_team_role_entries_for_this_team(): void
    {
        $mine = $this->audit('team.role_changed', $this->team->id);
        $portal = $this->audit('portal.invited', $this->team->id);
        $otherTeam = $this->audit('team.role_changed', Team::factory()->create()->id);

        Livewire::test(ListTeamRoleLogs::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$portal, $otherTeam]);
    }

    public function test_access_is_admin_only(): void
    {
        $this->assertTrue(TeamRoleLogResource::canAccess());

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $this->assertFalse(TeamRoleLogResource::canAccess());
    }
}
