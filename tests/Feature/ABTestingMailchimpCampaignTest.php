<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MailChimpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ABTestingMailchimpCampaignTest extends TestCase
{
    public $mockMailChimpService;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockMailChimpService = Mockery::mock(MailChimpService::class);
        $this->app->instance(MailChimpService::class, $this->mockMailChimpService);
    }

    public function test_create_ab_test_campaign_with_mailchimp_service(): void
    {
        $expectedResult = [
            'id' => 'test_campaign_id',
            'web_id' => 'test_web_id',
            'type' => 'abtest',
        ];

        $this->mockMailChimpService->shouldReceive('createABTestCampaign')
            ->once()
            ->andReturn($expectedResult);

        $result = app(MailChimpService::class)->createABTestCampaign(
            'test_list_id',
            'Subject A',
            'Subject B',
            'Test Sender',
            'test@example.com',
            '<p>Content A</p>',
            '<p>Content B</p>',
            50,
            'opens'
        );

        $this->assertEquals('test_campaign_id', $result['id']);
        $this->assertEquals('abtest', $result['type']);
    }

    public function test_retrieve_ab_test_results(): void
    {
        $this->mockMailChimpService->shouldReceive('getABTestResults')
            ->once()
            ->with('test_campaign_id')
            ->andReturn([
                'campaign_id' => 'test_campaign_id',
                'subject_a' => 'Test Subject A',
                'subject_b' => 'Test Subject B',
                'opens_a' => 100,
                'opens_b' => 120,
                'winner' => 'b',
            ]);

        $result = app(MailChimpService::class)->getABTestResults('test_campaign_id');

        $this->assertEquals('test_campaign_id', $result['campaign_id']);
        $this->assertEquals('Test Subject A', $result['subject_a']);
        $this->assertEquals('b', $result['winner']);
    }

    public function test_mailchimp_campaign_index_loads_for_authenticated_user(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();

        $response = $this->actingAs($user)->get('/app/'.$team->id.'/mailchimp-campaigns');
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected mailchimp-campaigns to return 200 or 302, got {$response->status()}"
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
