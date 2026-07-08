<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AuditLogResource;
use App\Filament\App\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppAuditLogResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function entry(string $action, int $teamId, int $userId): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $action.' entry',
            'ip_address' => '127.0.0.1',
            'team_id' => $teamId,
        ]);
    }

    private function actAsAdmin(): User
    {
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $admin->currentTeam;
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $admin;
    }

    public function test_lists_only_own_team_entries(): void
    {
        $admin = $this->actAsAdmin();
        $team = $admin->currentTeam;

        $mine = $this->entry('updated', $team->id, $admin->id);
        $otherTeam = $this->entry('updated', Team::factory()->create()->id, $admin->id);

        Livewire::test(ListAuditLogs::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$otherTeam]);
    }

    public function test_resource_is_read_only(): void
    {
        $this->assertFalse(AuditLogResource::canCreate());
    }

    public function test_access_gated_to_admins(): void
    {
        $team = Team::factory()->create();

        $admin = User::factory()->create();
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $this->assertTrue(AuditLogResource::canAccess());

        $rep = User::factory()->create();
        setPermissionsTeamId($team->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);
        $this->assertFalse(AuditLogResource::canAccess());
    }

    public function test_record_changes_filter_hides_team_entries(): void
    {
        $admin = $this->actAsAdmin();
        $team = $admin->currentTeam;

        $recordChange = $this->entry('updated', $team->id, $admin->id);
        $teamChange = $this->entry('team.role_changed', $team->id, $admin->id);

        Livewire::test(ListAuditLogs::class)
            ->filterTable('category', 'record')
            ->assertCanSeeTableRecords([$recordChange])
            ->assertCanNotSeeTableRecords([$teamChange]);
    }
}
