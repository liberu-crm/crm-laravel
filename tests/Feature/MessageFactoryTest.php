<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MessageFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_persists_a_row_using_real_columns(): void
    {
        $message = Message::factory()->create();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'channel' => $message->channel,
            'sender' => $message->sender,
            'content' => $message->content,
            'priority' => $message->priority,
            'status' => $message->status,
            'account_id' => $message->account_id,
            'team_id' => $message->team_id,
        ]);
    }

    public function test_casts_and_relations_resolve(): void
    {
        $message = Message::factory()->create();

        $this->assertIsArray($message->metadata);
        $this->assertInstanceOf(Carbon::class, $message->timestamp);
        $this->assertInstanceOf(Team::class, $message->team);
    }
}
