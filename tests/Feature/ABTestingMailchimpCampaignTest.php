<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MailChimpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ABTestingMailchimpCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockMailChimpService = Mockery::mock(MailChimpService::class);
        $this->app->instance(MailChimpService::class, $this->mockMailChimpService);
    }

    public function testCreateABTestCampaign()
    {
        $user = User::factory()->create();

        $this->mockMailChimpService->shouldReceive('createABTestCampaign')
            ->once()
            ->with(
                'test_list_id',
                'Subject A',
                'Subject B',
                'Test Sender',
                'test@example.com',
                '<p>Content A</p>',
                '<p>Content B</p>',
                50,
                'opens'
            )
            ->andReturn([
                'id' => 'test_campaign_id',
                'web_id' => 'test_web_id',
                'type' => 'abtest',
            ]);

        $response = $this->actingAs($user)->post('/mailchimp-campaigns', [
            'type' => 'abtest',
            'list_id' => 'test_list_id',
            'subject_line_a' => 'Subject A',
            'subject_line_b' => 'Subject B',
            'from_name' => 'Test Sender',
            'reply_to' => 'test@example.com',
            'content_a' => '<p>Content A</p>',
            'content_b' => '<p>Content B</p>',
            'test_size' => 50,
            'winner_criteria' => 'opens',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('filament.app.resources.mailchimp-campaigns.index'));
    }

    public function testRetrieveABTestResults()
    {
        $user = User::factory()->create();

        $this->mockMailChimpService->shouldReceive('getABTestResults')
            ->once()
            ->with('test_campaign_id')
            ->andReturn([
                'campaign_id' => 'test_campaign_id',
                'subject_a' => 'Test Subject A',
                'subject_b' => 'Test Subject B',
                'opens_a' => 100,
                'opens_b' => 120,
                'clicks_a' => 50,
                'clicks_b' => 60,
                'winner' => 'b',
                'winning_metric' => 'opens',
                'winning_metric_value' => 120,
            ]);

        $response = $this->actingAs($user)
            ->get('/mailchimp-campaigns/test_campaign_id/ab-test-results');

        $response->assertStatus(200);
        $response->assertViewIs('filament.app.resources.mailchimp-campaign-resource.pages.view-a-b-test-results');
        $response->assertViewHas('record');
        $response->assertSee('Test Subject A');
        $response->assertSee('Test Subject B');
        $response->assertSee('100');
        $response->assertSee('120');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}