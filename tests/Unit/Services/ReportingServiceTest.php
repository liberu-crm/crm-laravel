<?php

namespace Tests\Unit\Services;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reportingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportingService = new ReportingService();
    }

    public function testGetContactInteractionsData()
    {
        Contact::factory()->count(5)->create();
        Activity::factory()->count(20)->create();

        $result = $this->reportingService->getContactInteractionsData();

        $this->assertCount(5, $result);
        $this->assertArrayHasKey('activities_count', $result->first());
    }

    public function testGetSalesPipelineData()
    {
        Deal::factory()->count(10)->create();

        $result = $this->reportingService->getSalesPipelineData();

        $this->assertGreaterThan(0, $result->count());
        $this->assertArrayHasKey('stage', $result->first());
        $this->assertArrayHasKey('count', $result->first());

        $this->assertArrayHasKey('total_value', $result->first());
    }

    public function testGetCustomerEngagementData()
    {
        Activity::factory()->count(30)->create();

        $result = $this->reportingService->getCustomerEngagementData();

        $this->assertGreaterThan(0, $result->count());
        $this->assertArrayHasKey('date', $result->first());
        $this->assertArrayHasKey('count', $result->first());
    }
}