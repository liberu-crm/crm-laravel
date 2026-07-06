<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // user_id on audit_logs is NOT NULL + FK -> need an authenticated user.
        $this->actingAs(User::factory()->create());
    }

    public function test_creating_a_contact_writes_a_created_audit_log(): void
    {
        $contact = Contact::factory()->create();

        $this->assertDatabaseHas('audit_logs', ['action' => 'created']);
        $this->assertSame(1, AuditLog::count());
        // description carries the audited model class + id.
        $this->assertStringContainsString(
            $contact::class.'#'.$contact->getKey(),
            (string) AuditLog::first()->description,
        );
    }

    public function test_updating_a_contact_writes_an_updated_audit_log(): void
    {
        $contact = Contact::factory()->create();
        $contact->update(['name' => 'Renamed']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'updated']);
        // updated log records the changed attributes.
        $updated = AuditLog::where('action', 'updated')->firstOrFail();
        $this->assertStringContainsString('Renamed', (string) $updated->description);
    }

    public function test_deleting_a_contact_writes_a_deleted_audit_log(): void
    {
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted']);
    }

    public function test_no_infinite_recursion(): void
    {
        $contact = Contact::factory()->create(); // created
        $contact->update(['name' => 'X']);       // updated
        $contact->delete();                      // deleted

        // Exactly one row per event, and writing AuditLog itself is not audited.
        $this->assertSame(3, AuditLog::count());
    }
}
