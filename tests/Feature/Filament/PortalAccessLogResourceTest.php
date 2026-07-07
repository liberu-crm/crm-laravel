<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\PortalAccessLogResource;
use App\Filament\App\Resources\PortalAccessLogResource\Pages\ListPortalAccessLogs;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalAccessLogResourceTest extends TestCase
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

    public function test_lists_only_own_team_portal_entries(): void
    {
        $manager = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $manager->currentTeam;
        setPermissionsTeamId($team->id);
        $manager->assignRole('manager');
        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        $invited = $this->entry('portal.invited', $team->id, $manager->id);
        $revoked = $this->entry('portal.revoked', $team->id, $manager->id);
        $nonPortal = $this->entry('login', $team->id, $manager->id);
        $otherTeam = $this->entry('portal.invited', Team::factory()->create()->id, $manager->id);

        Livewire::test(ListPortalAccessLogs::class)
            ->assertCanSeeTableRecords([$invited, $revoked])
            ->assertCanNotSeeTableRecords([$nonPortal, $otherTeam]);
    }

    public function test_access_gated_to_managers(): void
    {
        $team = Team::factory()->create();

        $manager = User::factory()->create();
        setPermissionsTeamId($team->id);
        $manager->assignRole('manager');
        $this->actingAs($manager);
        $this->assertTrue(PortalAccessLogResource::canAccess());

        $rep = User::factory()->create();
        setPermissionsTeamId($team->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);
        $this->assertFalse(PortalAccessLogResource::canAccess());
    }
}
