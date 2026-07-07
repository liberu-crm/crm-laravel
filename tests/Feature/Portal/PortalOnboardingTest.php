<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Portal\InvitePortalCustomer;
use App\Exceptions\PortalOnboardingException;
use App\Filament\App\Resources\ContactResource\Pages\ListContacts;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Notifications\PortalInvitation;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PortalOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function contactFor(Team $team, ?string $email = 'customer@example.com'): Contact
    {
        return Contact::factory()->create([
            'team_id' => $team->id,
            'name' => 'Jane Customer',
            'email' => $email,
        ]);
    }

    public function test_invites_a_contact_as_portal_customer(): void
    {
        Notification::fake();
        $team = Team::factory()->create();
        $contact = $this->contactFor($team);

        $user = app(InvitePortalCustomer::class)($contact);

        $this->assertSame('customer@example.com', $user->email);
        $this->assertSame($team->id, $user->getAttribute('current_team_id'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotEmpty($user->password);

        setPermissionsTeamId(null);
        $this->assertTrue($user->fresh()->hasRole('customer'));

        Notification::assertSentTo($user, PortalInvitation::class);
    }

    public function test_refuses_when_email_belongs_to_a_staff_user(): void
    {
        Notification::fake();
        $team = Team::factory()->create();
        $staff = User::factory()->create(['email' => 'customer@example.com']);
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');

        $contact = $this->contactFor($team);

        $this->expectException(PortalOnboardingException::class);
        try {
            app(InvitePortalCustomer::class)($contact);
        } finally {
            setPermissionsTeamId($team->id);
            $this->assertTrue($staff->fresh()->hasRole('manager'));
            $this->assertFalse($staff->fresh()->hasRole('customer'));
            Notification::assertNothingSent();
        }
    }

    public function test_refuses_a_contact_without_an_email(): void
    {
        // contacts.email is NOT NULL, so the real guard target is a blank email.
        $team = Team::factory()->create();
        $contact = $this->contactFor($team, '');

        $this->expectException(PortalOnboardingException::class);
        app(InvitePortalCustomer::class)($contact);
    }

    public function test_reinvite_does_not_duplicate_the_user(): void
    {
        Notification::fake();
        $team = Team::factory()->create();
        $contact = $this->contactFor($team);

        $first = app(InvitePortalCustomer::class)($contact);
        $second = app(InvitePortalCustomer::class)($contact);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, User::where('email', 'customer@example.com')->count());
        Notification::assertSentToTimes($first, PortalInvitation::class, 2);
    }

    public function test_portal_password_reset_route_is_available(): void
    {
        $this->get('/portal/password-reset/request')->assertOk();
    }

    public function test_contact_resource_action_invites_the_contact(): void
    {
        Notification::fake();
        $staff = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $staff->currentTeam;
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');
        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        $contact = $this->contactFor($team, 'lead@example.com');

        Livewire::test(ListContacts::class)
            ->callTableAction('inviteToPortal', $contact);

        $user = User::where('email', 'lead@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($team->id, $user->getAttribute('current_team_id'));
        setPermissionsTeamId(null);
        $this->assertTrue($user->hasRole('customer'));
        Notification::assertSentTo($user, PortalInvitation::class);
    }
}
