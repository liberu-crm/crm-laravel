<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Activity;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitySearchTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_activity_full_text_search()
    {
        $contact = Contact::factory()->create();
        
        $activity1 = Activity::factory()->create([
            'type' => 'call',
            'description' => 'Discussed new product features',
            'outcome' => 'Positive feedback received',
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $activity2 = Activity::factory()->create([
            'type' => 'email',
            'description' => 'Sent follow-up email',
            'outcome' => 'No response yet',
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/activities?search=product features');

        $response->assertStatus(200);
        $response->assertSee($activity1->description);
        $response->assertDontSee($activity2->description);
    }

    public function test_activity_advanced_filtering()
    {
        $contact = Contact::factory()->create();
        
        $activity1 = Activity::factory()->create([
            'type' => 'call',
            'date' => now()->subDays(5),
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $activity2 = Activity::factory()->create([
            'type' => 'email',
            'date' => now()->subDays(10),
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/activities?type=call&date_start=' . now()->subDays(7)->toDateString() . '&date_end=' . now()->toDateString());

        $response->assertStatus(200);

        $response->assertSee($activity1->type);
        $response->assertDontSee($activity2->type);
    }

    public function test_activity_filtering_by_activitable_type()
    {
        $contact = Contact::factory()->create();
        $lead = Lead::factory()->create();
        
        $activity1 = Activity::factory()->create([
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $activity2 = Activity::factory()->create([
            'activitable_id' => $lead->id,
            'activitable_type' => Lead::class,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/activities?activitable_type=' . Contact::class);

        $response->assertStatus(200);
        $response->assertSee($activity1->description);
        $response->assertDontSee($activity2->description);
    }
}