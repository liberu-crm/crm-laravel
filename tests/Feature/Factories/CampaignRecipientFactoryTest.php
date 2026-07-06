<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignRecipientFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_persists_a_valid_row_with_real_columns(): void
    {
        $recipient = CampaignRecipient::factory()->create();

        $this->assertDatabaseHas('campaign_recipients', [
            'id' => $recipient->id,
            'marketing_campaign_id' => $recipient->marketing_campaign_id,
            'recipient_type' => Contact::class,
            'recipient_id' => $recipient->recipient_id,
            'status' => $recipient->status,
        ]);

        $this->assertContains($recipient->status, ['pending', 'sent', 'failed']);
        $this->assertInstanceOf(Contact::class, $recipient->recipient);
    }

    public function test_lead_mass_assigns_team_id(): void
    {
        // Mass-assign via Model::create so fillable is actually enforced (a
        // factory build runs unguarded and would pass regardless). The column
        // defaults to 1, so the target team must NOT be id 1 or a dropped
        // team_id would coincidentally match.
        Team::factory()->create();
        $team = Team::factory()->create();
        $this->assertNotSame(1, $team->id);

        $lead = Lead::create(['team_id' => $team->id]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_marketing_campaign_mass_assigns_team_id(): void
    {
        Team::factory()->create();
        $team = Team::factory()->create();
        $this->assertNotSame(1, $team->id);

        $campaign = MarketingCampaign::create([
            'name' => 'Test Campaign',
            'type' => 'email',
            'status' => 'draft',
            'content' => 'Body',
            'team_id' => $team->id,
        ]);

        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'team_id' => $team->id,
        ]);
    }
}
