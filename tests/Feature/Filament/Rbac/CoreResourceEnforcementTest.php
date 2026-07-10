<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Rbac;

use App\Filament\App\Resources\ContactResource;
use App\Filament\App\Resources\DealResource;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreResourceEnforcementTest extends TestCase
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

    public function test_admin_has_full_crud_on_contacts(): void
    {
        $this->actAs('admin');

        $this->assertTrue(ContactResource::canViewAny());
        $this->assertTrue(ContactResource::canCreate());
        $this->assertTrue(ContactResource::canDeleteAny());
    }

    public function test_free_is_a_limited_editor_but_cannot_delete_contacts(): void
    {
        $this->actAs('free');

        // free is a limited editor: view/create/update but never delete.
        $this->assertTrue(ContactResource::canViewAny());
        $this->assertTrue(ContactResource::canCreate());
        $this->assertFalse(ContactResource::canDeleteAny());

        $contact = Contact::factory()->create(['team_id' => $this->app['auth']->user()->currentTeam->id]);
        $this->assertTrue(ContactResource::canEdit($contact));
        $this->assertFalse(ContactResource::canDelete($contact));
    }

    public function test_sales_rep_crud_core_records(): void
    {
        $this->actAs('sales_rep');

        $this->assertTrue(DealResource::canViewAny());
        $this->assertTrue(DealResource::canCreate());
    }

    public function test_customer_cannot_touch_core_records(): void
    {
        $this->actAs('customer');

        $this->assertFalse(ContactResource::canViewAny());
        $this->assertFalse(DealResource::canViewAny());
    }

    public function test_super_admin_bypasses_enforcement(): void
    {
        $this->actAs('super_admin');

        $this->assertTrue(ContactResource::canViewAny());
        $this->assertTrue(ContactResource::canCreate());
        $this->assertTrue(ContactResource::canDeleteAny());
    }
}
