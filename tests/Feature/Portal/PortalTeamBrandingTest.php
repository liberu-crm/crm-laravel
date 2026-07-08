<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\App\Resources\PortalBrandingResource;
use App\Filament\App\Resources\PortalBrandingResource\Pages\EditPortalBranding;
use App\Models\Team;
use App\Models\User;
use App\Support\PortalBranding;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalTeamBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        config(['portal.brand_name' => 'Default Portal', 'portal.logo' => 'https://cdn/default.png']);
    }

    private function actAsCustomer(Team $team): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['current_team_id' => $team->id])->save();
        setPermissionsTeamId(null);
        $user->assignRole('customer');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $user;
    }

    public function test_branding_resolves_the_customers_team_values(): void
    {
        $team = Team::factory()->create(['portal_brand_name' => 'Acme Support', 'portal_logo_url' => 'https://cdn/acme.png']);
        $this->actAsCustomer($team);

        $this->assertSame('Acme Support', PortalBranding::brandName());
        $this->assertSame('https://cdn/acme.png', PortalBranding::logo());
    }

    public function test_branding_falls_back_to_config(): void
    {
        $team = Team::factory()->create(['portal_brand_name' => null, 'portal_logo_url' => null]);
        $this->actAsCustomer($team);

        $this->assertSame('Default Portal', PortalBranding::brandName());
        $this->assertSame('https://cdn/default.png', PortalBranding::logo());
    }

    public function test_portal_panel_brand_name_reflects_the_team(): void
    {
        $team = Team::factory()->create(['portal_brand_name' => 'Acme Support']);
        $this->actAsCustomer($team);

        $this->assertSame('Acme Support', Filament::getPanel('portal')->getBrandName());
    }

    public function test_resource_access_and_edit(): void
    {
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $admin->currentTeam;
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        $this->assertTrue(PortalBrandingResource::canAccess());

        Livewire::test(EditPortalBranding::class, ['record' => $team->getKey()])
            ->fillForm(['portal_brand_name' => 'My Brand', 'portal_logo_url' => 'https://cdn/mine.png'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('My Brand', $team->fresh()->getAttribute('portal_brand_name'));

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);
        $this->assertFalse(PortalBrandingResource::canAccess());
    }
}
