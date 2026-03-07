<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\Lead;
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

    public function test_activity_can_be_created_for_contact()
    {
        $contact = Contact::factory()->create();

        $activity = Activity::factory()->create([
            'type' => 'call',
            'description' => 'Discussed new product features',
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'type' => 'call',
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);
    }

    public function test_activity_can_be_searched_by_type()
    {
        $contact = Contact::factory()->create();

        $callActivity = Activity::factory()->create([
            'type' => 'call',
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $emailActivity = Activity::factory()->create([
            'type' => 'email',
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $callActivities = Activity::where('type', 'call')->get();
        $emailActivities = Activity::where('type', 'email')->get();

        $this->assertCount(1, $callActivities);
        $this->assertCount(1, $emailActivities);
        $this->assertEquals($callActivity->id, $callActivities->first()->id);
    }

    public function test_activity_can_be_filtered_by_activitable_type()
    {
        $contact = Contact::factory()->create();
        $lead = Lead::factory()->create();

        $contactActivity = Activity::factory()->create([
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $leadActivity = Activity::factory()->create([
            'activitable_id' => $lead->id,
            'activitable_type' => Lead::class,
        ]);

        $contactActivities = Activity::where('activitable_type', Contact::class)->get();
        $this->assertTrue($contactActivities->contains($contactActivity));
        $this->assertFalse($contactActivities->contains($leadActivity));
    }

    public function test_activities_can_be_filtered_by_date_range()
    {
        $contact = Contact::factory()->create();

        $recentActivity = Activity::factory()->create([
            'date' => now()->subDays(5),
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $oldActivity = Activity::factory()->create([
            'date' => now()->subDays(30),
            'activitable_id' => $contact->id,
            'activitable_type' => Contact::class,
        ]);

        $results = Activity::whereBetween('date', [now()->subDays(7), now()])->get();

        $this->assertTrue($results->contains($recentActivity));
        $this->assertFalse($results->contains($oldActivity));
    }
}
