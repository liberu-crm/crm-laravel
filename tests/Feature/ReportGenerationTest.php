<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function testContactInteractionsReportGeneration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports/contact-interactions');

        $response->assertStatus(200);
        $response->assertViewIs('reports.contact-interactions');
        $response->assertViewHas('data');
    }

    public function testSalesPipelineReportGeneration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports/sales-pipeline');

        $response->assertStatus(200);
        $response->assertViewIs('reports.sales-pipeline');
        $response->assertViewHas('data');
    }

    public function testCustomerEngagementReportGeneration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports/customer-engagement');

        $response->assertStatus(200);
        $response->assertViewIs('reports.customer-engagement');
        $response->assertViewHas('data');
    }
}