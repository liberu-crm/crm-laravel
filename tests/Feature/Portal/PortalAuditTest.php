<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Portal\InvitePortalCustomer;
use App\Actions\Portal\RevokePortalCustomer;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Notification::fake();
    }

    private function actingStaff(Team $team): User
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');
        $this->actingAs($staff);

        return $staff;
    }

    public function test_invite_writes_an_audit_entry(): void
    {
        $team = Team::factory()->create();
        $staff = $this->actingStaff($team);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'c@example.com']);

        $customer = app(InvitePortalCustomer::class)($contact);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => 'portal.invited',
            'auditable_type' => $customer->getMorphClass(),
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_revoke_writes_an_audit_entry(): void
    {
        $team = Team::factory()->create();
        $staff = $this->actingStaff($team);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'c@example.com']);
        $customer = app(InvitePortalCustomer::class)($contact);

        app(RevokePortalCustomer::class)($contact);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => 'portal.revoked',
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_no_audit_entry_without_an_actor(): void
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'c@example.com']);

        // No actingAs — Auth::check() is false, so the entry is skipped.
        app(InvitePortalCustomer::class)($contact);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'portal.invited']);
    }
}
