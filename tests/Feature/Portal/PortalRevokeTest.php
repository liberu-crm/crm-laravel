<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Portal\InvitePortalCustomer;
use App\Actions\Portal\RevokePortalCustomer;
use App\Exceptions\PortalOnboardingException;
use App\Filament\App\Resources\ContactResource\Pages\ListContacts;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PortalRevokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    /**
     * @return array{0: Contact, 1: User}
     */
    private function provision(Team $team, string $email = 'cust@example.com'): array
    {
        Notification::fake();
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => $email]);
        $user = app(InvitePortalCustomer::class)($contact);

        return [$contact, $user];
    }

    public function test_revokes_customer_role_but_keeps_user(): void
    {
        $team = Team::factory()->create();
        [$contact, $user] = $this->provision($team);
        setPermissionsTeamId(null);
        $this->assertTrue($user->fresh()->hasRole('customer'));

        app(RevokePortalCustomer::class)($contact);

        setPermissionsTeamId(null);
        $this->assertFalse($user->fresh()->hasRole('customer'));
        $this->assertNotNull(User::find($user->id));
        $this->assertSame($team->id, $user->fresh()->getAttribute('current_team_id'));
    }

    public function test_revoked_customer_cannot_access_portal(): void
    {
        $team = Team::factory()->create();
        [$contact, $user] = $this->provision($team);

        app(RevokePortalCustomer::class)($contact);

        $this->actingAs($user->fresh());
        Filament::setCurrentPanel(Filament::getPanel('portal'));
        $this->get('/portal/tickets')->assertForbidden();
    }

    public function test_refuses_when_not_a_portal_customer(): void
    {
        $team = Team::factory()->create();
        $staff = User::factory()->create(['email' => 'staff@example.com']);
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'staff@example.com']);

        $this->expectException(PortalOnboardingException::class);
        try {
            app(RevokePortalCustomer::class)($contact);
        } finally {
            setPermissionsTeamId($team->id);
            $this->assertTrue($staff->fresh()->hasRole('manager'));
        }
    }

    public function test_contact_resource_revoke_action(): void
    {
        $staff = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $staff->currentTeam;
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');
        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        [$contact, $user] = $this->provision($team, 'lead@example.com');

        // provision() resets the permission team context to null; restore it so
        // the manager's core-CRM permissions resolve for the ListContacts mount.
        setPermissionsTeamId($team->id);

        Livewire::test(ListContacts::class)
            ->callTableAction('revokePortalAccess', $contact);

        setPermissionsTeamId(null);
        $this->assertFalse($user->fresh()->hasRole('customer'));
    }
}
