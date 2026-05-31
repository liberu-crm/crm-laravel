<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Services\MailChimpService;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_interactions_report_service(): void
    {
        Contact::factory()->count(3)->create();

        $service = app(ReportingService::class);
        $data = $service->getContactInteractionsData([]);

        $this->assertInstanceOf(Collection::class, $data);
    }

    public function test_sales_pipeline_report_service(): void
    {
        Deal::factory()->count(5)->create();

        $service = app(ReportingService::class);
        $data = $service->getSalesPipelineData([]);

        $this->assertInstanceOf(Collection::class, $data);
    }

    public function test_customer_engagement_report_service(): void
    {
        $service = app(ReportingService::class);
        $data = $service->getCustomerEngagementData([]);

        $this->assertInstanceOf(Collection::class, $data);
    }

    public function test_ab_test_results_with_mocked_mailchimp(): void
    {
        $mockMailChimpService = Mockery::mock(MailChimpService::class);
        $mockMailChimpService->shouldReceive('getABTestResults')
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

        $this->app->instance(MailChimpService::class, $mockMailChimpService);

        $mailChimpService = app(MailChimpService::class);
        $data = $mailChimpService->getABTestResults('test_campaign_id');

        $this->assertEquals('test_campaign_id', $data['campaign_id']);
        $this->assertEquals('Test Subject A', $data['subject_a']);
        $this->assertEquals(120, $data['opens_b']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
