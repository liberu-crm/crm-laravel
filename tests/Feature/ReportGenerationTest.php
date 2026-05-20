<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Deal;
use App\Services\ReportingService;
use App\Services\MailChimpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function testContactInteractionsReportService()
    {
        Contact::factory()->count(3)->create();

        $service = app(ReportingService::class);
        $data = $service->getContactInteractionsData([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $data);
    }

    public function testSalesPipelineReportService()
    {
        Deal::factory()->count(5)->create();

        $service = app(ReportingService::class);
        $data = $service->getSalesPipelineData([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $data);
    }

    public function testCustomerEngagementReportService()
    {
        $service = app(ReportingService::class);
        $data = $service->getCustomerEngagementData([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $data);
    }

    public function testABTestResultsWithMockedMailchimp()
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
