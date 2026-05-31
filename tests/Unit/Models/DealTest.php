<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealTest extends TestCase
{
    use RefreshDatabase;

    public function test_deal_can_be_created(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->assertDatabaseHas('deals', ['id' => $deal->id]);
    }

    public function test_deal_belongs_to_contact(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        $deal = Deal::factory()->create([
            'team_id' => $user->currentTeam->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertInstanceOf(Contact::class, $deal->contact);
        $this->assertEquals($contact->id, $deal->contact->id);
    }

    public function test_deal_value_is_cast_to_decimal(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $deal = Deal::factory()->create([
            'team_id' => $user->currentTeam->id,
            'value' => 12345.67,
        ]);

        $this->assertEquals('12345.67', $deal->value);
    }

    public function test_deal_close_date_is_cast_to_date(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $deal = Deal::factory()->create([
            'team_id' => $user->currentTeam->id,
            'close_date' => '2025-12-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $deal->close_date);
    }

    public function test_deal_has_many_activities(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->assertNotNull($deal->activities());
    }

    public function test_deal_has_many_documents(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->assertNotNull($deal->documents());
    }
}
