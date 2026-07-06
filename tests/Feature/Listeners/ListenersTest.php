<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Enums\Role;
use App\Events\ContactUpdated;
use App\Listeners\CreatePersonalTeam;
use App\Listeners\EmailTracker;
use App\Listeners\NotifyTeamMembers;
use App\Listeners\SendCRMEventNotification;
use App\Listeners\SwitchTeam;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CRMEventNotification;
use App\Services\TeamManagementService;
use Database\Seeders\RolesSeeder;
use Filament\Events\TenantSet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Notification;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class ListenersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class); // global (team_id = null) role definitions
    }

    protected function tearDown(): void
    {
        // Per-team permission context is a process static; reset so it can't leak.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    /** Spatie roles are cached per team; refresh after switching the team context. */
    private function hasRoleInTeam(User $user, ?Team $team, Role $role): bool
    {
        setPermissionsTeamId($team?->getKey());
        $user->unsetRelation('roles');

        return $user->hasRole($role);
    }

    // --- AssignDefaultTeamRole (TeamMemberAdded) ---------------------------------

    public function test_assign_default_team_role_gives_added_member_sales_rep_in_that_team(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();

        // Dispatched → EventServiceProvider routes it to AssignDefaultTeamRole.
        event(new TeamMemberAdded($team, $member));

        $this->assertTrue($this->hasRoleInTeam($member, $team, Role::SalesRep));
        // Scoped to that team only.
        $this->assertFalse($this->hasRoleInTeam($member, Team::factory()->create(), Role::SalesRep));
    }

    // --- CreatePersonalTeam (Registered) ----------------------------------------

    public function test_create_personal_team_assigns_registered_user_to_default_team(): void
    {
        $defaultTeam = Team::factory()->create(['personal_team' => false]);
        $user = User::factory()->create();

        app(CreatePersonalTeam::class)->handle(new Registered($user));

        $this->assertTrue($user->fresh()->belongsToTeam($defaultTeam));
        $this->assertSame($defaultTeam->id, $user->fresh()->current_team_id);
    }

    // --- SwitchTeam (Filament TenantSet) ----------------------------------------

    public function test_switch_team_scopes_spatie_permissions_to_the_tenant(): void
    {
        $team = Team::factory()->create();
        setPermissionsTeamId(null);

        (new SwitchTeam())->handle(new TenantSet($team, User::factory()->make()));

        $this->assertSame($team->getKey(), getPermissionsTeamId());
    }

    public function test_switch_team_ignores_non_tenant_set_events(): void
    {
        setPermissionsTeamId(null);

        (new SwitchTeam())->handle(new \stdClass());

        $this->assertNull(getPermissionsTeamId());
    }

    // --- SendCRMEventNotification (config-gated CRM events) ----------------------

    public function test_send_crm_event_notification_notifies_users_for_configured_event(): void
    {
        Notification::fake();
        $users = User::factory()->count(2)->create();

        // class_basename(NewLead) → snake → "new_lead", a truthy config/crm.php key.
        (new SendCRMEventNotification())->handle(new NewLead());

        Notification::assertSentTo($users, CRMEventNotification::class);
    }

    public function test_send_crm_event_notification_skips_unconfigured_event(): void
    {
        Notification::fake();
        User::factory()->create();

        (new SendCRMEventNotification())->handle(new SomethingUnconfigured());

        Notification::assertNothingSent();
    }

    // --- NotifyTeamMembers (ContactUpdated) -------------------------------------

    public function test_notify_team_members_handles_event_without_error(): void
    {
        // ponytail: handle() is a comment-only stub (see flag in summary); the only
        // guarantee it currently makes is that dispatching the event is safe.
        (new NotifyTeamMembers())->handle(new ContactUpdated(new Contact()));

        $this->assertTrue(true);
    }

    // --- EmailTracker (MessageSent) ---------------------------------------------

    public function test_email_tracker_is_a_noop_when_campaign_headers_absent(): void
    {
        // A normal outbound mail carries no X-Campaign-ID/X-Lead-ID, so the guard
        // short-circuits before the (broken) EmailCampaign path. See flag in summary.
        $email = (new Email())
            ->from('sender@example.com')
            ->to('rcpt@example.com')
            ->subject('Hello')
            ->text('body');

        $symfonySent = new SymfonySentMessage(
            $email,
            new Envelope(new Address('sender@example.com'), [new Address('rcpt@example.com')]),
        );

        (new EmailTracker())->handle(new MessageSent(new SentMessage($symfonySent)));

        $this->assertTrue(true); // no exception on the header-absent path
    }
}

/**
 * Test double for SendCRMEventNotification: class_basename → "NewLead" →
 * Str::snake → "new_lead", which config/crm.php marks true.
 */
final class NewLead
{
    public function toArray(): array
    {
        return ['lead_id' => 42];
    }
}

/** Test double with no matching config/crm.php key. */
final class SomethingUnconfigured
{
    public function toArray(): array
    {
        return [];
    }
}
