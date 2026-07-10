<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Rbac;

use App\Filament\App\Resources\AuditLogResource;
use App\Filament\App\Resources\CampaignResource;
use App\Filament\App\Resources\PortalAccessLogResource;
use App\Filament\App\Resources\SsoConnectionResource;
use App\Filament\App\Resources\TerritoryResource;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class ResourceSweepEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(string $role): User
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $user;
    }

    public function test_advertising_group_tightens_by_role(): void
    {
        $this->actAs('admin');
        $this->assertTrue(CampaignResource::canViewAny());
        $this->assertTrue(CampaignResource::canCreate());

        $this->actAs('manager');
        $this->assertTrue(CampaignResource::canViewAny());
        $this->assertTrue(CampaignResource::canCreate());

        // sales_rep: view advertising, cannot create.
        $this->actAs('sales_rep');
        $this->assertTrue(CampaignResource::canViewAny());
        $this->assertFalse(CampaignResource::canCreate());

        // free: no advertising access at all.
        $this->actAs('free');
        $this->assertFalse(CampaignResource::canViewAny());
    }

    public function test_security_settings_are_admin_only(): void
    {
        $this->actAs('admin');
        $this->assertTrue(SsoConnectionResource::canViewAny());

        $this->actAs('manager');
        $this->assertFalse(SsoConnectionResource::canViewAny());

        $this->actAs('sales_rep');
        $this->assertFalse(SsoConnectionResource::canViewAny());

        $this->actAs('super_admin');
        $this->assertTrue(SsoConnectionResource::canViewAny());
    }

    public function test_territory_is_admin_and_manager(): void
    {
        $this->actAs('admin');
        $this->assertTrue(TerritoryResource::canViewAny());

        $this->actAs('manager');
        $this->assertTrue(TerritoryResource::canViewAny());

        $this->actAs('free');
        $this->assertFalse(TerritoryResource::canViewAny());
    }

    public function test_log_access_matches_historical_gates(): void
    {
        // audit_log: admin yes, manager no (historical gate excluded manager).
        $this->actAs('admin');
        $this->assertTrue(AuditLogResource::canViewAny());

        $this->actAs('manager');
        $this->assertFalse(AuditLogResource::canViewAny());
        // but portal_access_log: manager yes (historical gate included manager).
        $this->assertTrue(PortalAccessLogResource::canViewAny());
    }

    public function test_custom_team_role_grants_scoped_access(): void
    {
        // A member with only a custom team role that grants view_campaign can see
        // Campaigns but not, say, security settings.
        $user = $this->actAs('free');
        $team = $user->currentTeam;

        $custom = SpatieRole::create([
            'name' => 'ad_viewer',
            'guard_name' => 'web',
            'team_id' => $team->id,
        ]);
        $custom->givePermissionTo('view_campaign');
        setPermissionsTeamId($team->id);
        $user->assignRole($custom);

        $this->assertTrue(CampaignResource::canViewAny());
        $this->assertFalse(SsoConnectionResource::canViewAny());
    }
}
