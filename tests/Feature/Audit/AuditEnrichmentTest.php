<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_contact_records_a_structured_audit_log(): void
    {
        $this->actingAs(User::factory()->create());

        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        $contact->update(['name' => 'Renamed']);

        $log = AuditLog::where('action', 'updated')
            ->where('auditable_type', Contact::class)
            ->where('auditable_id', $contact->getKey())
            ->firstOrFail();

        $this->assertSame('updated', $log->action);
        $this->assertSame(Contact::class, $log->auditable_type);
        $this->assertEquals($contact->getKey(), $log->auditable_id);

        // changes is cast to array and carries the changed attribute.
        $this->assertIsArray($log->changes);
        $this->assertArrayHasKey('name', $log->changes);
        $this->assertSame('Renamed', $log->changes['name']);

        // team_id is stamped from the audited model.
        $this->assertEquals($team->id, $log->team_id);

        // auditable morphs back to the Contact.
        $this->assertTrue($contact->is($log->auditable));
    }
}
