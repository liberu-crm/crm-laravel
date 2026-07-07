<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function actingCustomer(): User
    {
        $this->seed(RolesSeeder::class);
        $customer = User::factory()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $customer->assignRole('customer');
        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $customer;
    }

    public function test_portal_shows_default_brand_name(): void
    {
        $this->actingCustomer();

        $this->get('/portal')->assertOk()->assertSee('Customer Portal');
    }

    public function test_brand_name_is_configurable(): void
    {
        $this->actingCustomer();
        config(['portal.brand_name' => 'Acme Support']);

        $this->get('/portal')->assertSee('Acme Support');
    }
}
