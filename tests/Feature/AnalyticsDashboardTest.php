<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function testAnalyticsDashboardAccess()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('analytics-dashboard');
    }

    public function testAnalyticsDashboardContainsRequiredComponents()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        $response->assertSee('Contact Stats Overview');
        $response->assertSee('Sales Pipeline Chart');
        $response->assertSee('Customer Engagement Chart');
    }
}