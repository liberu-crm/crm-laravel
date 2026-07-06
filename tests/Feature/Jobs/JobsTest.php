<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ExecuteWorkflowAction;
use App\Jobs\SendMarketingCampaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\Team;
use App\Services\MailChimpService;
use App\Services\TwilioService;
use App\Services\WhatsAppBusinessService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the job's handle() the way the worker would: with the dispatching
     * team restored by the TenantAware middleware around it.
     */
    private function runInWorker(object $job, callable $handle): void
    {
        // The worker process is un-scoped; the middleware must restore context.
        TenantContext::clear();
        $job->middleware()[0]->handle($job, $handle);
    }

    public function test_execute_workflow_action_updates_the_leads_contact(): void
    {
        $team = Team::factory()->create();
        TenantContext::set($team->id);

        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'status' => 'inactive',
            'phone_number' => '000',
        ]);
        $lead = Lead::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
        ]);

        $job = new ExecuteWorkflowAction(
            ['type' => 'update_contact', 'data' => ['status' => 'active', 'phone_number' => '555-1234']],
            $lead,
        );
        // TenantAware captured the dispatching team at construct time.
        $this->assertSame($team->id, $job->tenantId);

        $this->runInWorker($job, fn ($j) => $j->handle());

        $contact->refresh();
        $this->assertSame('active', $contact->status);
        $this->assertSame('555-1234', $contact->phone_number);
    }

    public function test_execute_workflow_action_is_safe_when_lead_has_no_contact(): void
    {
        $team = Team::factory()->create();
        TenantContext::set($team->id);

        // contact_id is nullable and unset — the null-safe update must no-op.
        $lead = Lead::factory()->create(['team_id' => $team->id]);

        $job = new ExecuteWorkflowAction(
            ['type' => 'update_contact', 'data' => ['status' => 'active']],
            $lead,
        );

        $this->runInWorker($job, fn ($j) => $j->handle());

        $this->assertNull($lead->fresh()->contact_id);
    }

    public function test_send_marketing_campaign_marks_its_recipients_and_campaign_sent(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        // team_id is not fillable on these models — IsTenantModel auto-stamps it
        // from the active context on create, so set the context per row.
        TenantContext::set($team->id);
        $campaign = MarketingCampaign::factory()->create([
            'type' => 'email',
            'status' => 'draft',
        ]);
        $mine = CampaignRecipient::create([
            'marketing_campaign_id' => $campaign->id,
            'recipient_type' => Contact::class,
            'recipient_id' => 1,
            'email' => 'me@example.test',
            'status' => 'pending',
        ]);

        // Same campaign id, different team: TenantAware must keep this untouched.
        TenantContext::set($otherTeam->id);
        $leaked = CampaignRecipient::create([
            'marketing_campaign_id' => $campaign->id,
            'recipient_type' => Contact::class,
            'recipient_id' => 2,
            'email' => 'other@example.test',
            'status' => 'pending',
        ]);

        TenantContext::set($team->id);
        $job = new SendMarketingCampaign($campaign);
        $this->assertSame($team->id, $job->tenantId);

        // External senders are stubbed no-ops; mock them so nothing hits the network.
        $handle = fn ($j) => $j->handle(
            Mockery::mock(MailChimpService::class),
            Mockery::mock(TwilioService::class),
            Mockery::mock(WhatsAppBusinessService::class),
        );
        $this->runInWorker($job, $handle);

        $this->assertSame('sent', $campaign->fresh()->status);
        $this->assertSame('sent', $mine->fresh()->status);
        $this->assertSame('pending', $leaked->fresh()->status, 'cross-team recipient must not be marked sent');
    }
}
