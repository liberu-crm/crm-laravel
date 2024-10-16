<?php

namespace Tests\Feature\Api;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DealApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_can_list_deals()
    {
        Deal::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/deals');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_deal()
    {
        $dealData = [
            'title' => 'New Deal',
            'value' => 1000,
            'status' => 'open',
        ];

        $response = $this->postJson('/api/v1/deals', $dealData);

        $response->assertStatus(201)
            ->assertJsonFragment($dealData);
    }

    public function test_can_show_deal()
    {
        $deal = Deal::factory()->create();

        $response = $this->getJson("/api/v1/deals/{$deal->id}");

        $response->assertStatus(200)
            ->assertJson($deal->toArray());
    }

    public function test_can_update_deal()
    {
        $deal = Deal::factory()->create();
        $updatedData = [
            'title' => 'Updated Deal',
            'value' => 2000,
            'status' => 'won',
        ];

        $response = $this->putJson("/api/v1/deals/{$deal->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJsonFragment($updatedData);
    }

    public function test_can_delete_deal()
    {
        $deal = Deal::factory()->create();

        $response = $this->deleteJson("/api/v1/deals/{$deal->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
    }
}