<?php

namespace Tests\Feature\Tenancy;

use App\Models\Contact;
use App\Models\Team;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_global_scope_filters_reads_to_current_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        // Seed rows in each team while no tenant context is active.
        $a = Contact::factory()->create(['team_id' => $teamA->id]);
        $b = Contact::factory()->create(['team_id' => $teamB->id]);

        TenantContext::set($teamA->id);
        $ids = Contact::pluck('id');
        $this->assertTrue($ids->contains($a->id), 'team A must see its own contact');
        $this->assertFalse($ids->contains($b->id), 'team A must NOT see team B contact (leak)');

        TenantContext::set($teamB->id);
        $this->assertEqualsCanonicalizing([$b->id], Contact::pluck('id')->all());
    }

    public function test_no_context_is_unscoped_for_admin(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        Contact::factory()->create(['team_id' => $teamA->id]);
        Contact::factory()->create(['team_id' => $teamB->id]);

        TenantContext::clear();
        $this->assertCount(2, Contact::all(), 'un-scoped context (admin/console) sees all teams');
    }

    public function test_creating_auto_sets_team_id_from_context(): void
    {
        $team = Team::factory()->create();
        TenantContext::set($team->id);

        $contact = Contact::create([
            'name' => 'Auto',
            'email' => 'auto@example.test',
        ]);

        $this->assertSame($team->id, $contact->team_id);
    }

    public function test_explicit_team_id_is_not_overwritten_on_create(): void
    {
        $ctxTeam = Team::factory()->create();
        $explicitTeam = Team::factory()->create();
        TenantContext::set($ctxTeam->id);

        $contact = Contact::create([
            'name' => 'Explicit',
            'email' => 'explicit@example.test',
            'team_id' => $explicitTeam->id,
        ]);

        $this->assertSame($explicitTeam->id, $contact->team_id);
    }

    public function test_scope_can_be_bypassed_for_global_queries(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        Contact::factory()->create(['team_id' => $teamA->id]);
        Contact::factory()->create(['team_id' => $teamB->id]);

        TenantContext::set($teamA->id);
        $this->assertCount(2, Contact::withoutGlobalScope('tenant')->get());
    }
}
