<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\LiveChat;
use App\Models\User;
use App\Services\LiveChatService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private LiveChatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LiveChatService;
    }

    public function test_start_chat_creates_a_waiting_chat_with_visitor_data(): void
    {
        $chat = $this->service->startChat([
            'visitor_id' => 'visitor_abc',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $this->assertInstanceOf(LiveChat::class, $chat);
        $this->assertTrue($chat->exists);
        $this->assertSame(LiveChat::STATUS_WAITING, $chat->status);
        $this->assertSame('visitor_abc', $chat->visitor_id);
        $this->assertSame('Ada Lovelace', $chat->visitor_name);
        $this->assertSame('ada@example.com', $chat->visitor_email);
        $this->assertNotNull($chat->started_at);
        $this->assertDatabaseHas('live_chats', [
            'visitor_id' => 'visitor_abc',
            'status' => LiveChat::STATUS_WAITING,
        ]);
    }

    public function test_start_chat_defaults_visitor_name_when_missing(): void
    {
        $chat = $this->service->startChat([]);

        $this->assertSame('Anonymous', $chat->visitor_name);
        $this->assertNotEmpty($chat->visitor_id);
    }

    public function test_assign_chat_sets_agent_and_moves_status_to_active(): void
    {
        $chat = LiveChat::factory()->create(['status' => LiveChat::STATUS_WAITING]);
        $agent = User::factory()->create();

        $this->service->assignChat($chat, $agent->id);

        $chat->refresh();
        $this->assertSame($agent->id, $chat->user_id);
        $this->assertSame(LiveChat::STATUS_ACTIVE, $chat->status);
    }

    public function test_end_chat_marks_ended_and_stores_rating_and_feedback(): void
    {
        $chat = LiveChat::factory()->create(['status' => LiveChat::STATUS_ACTIVE]);

        $this->service->endChat($chat, 5, 'Great support');

        $chat->refresh();
        $this->assertSame(LiveChat::STATUS_ENDED, $chat->status);
        $this->assertNotNull($chat->ended_at);
        $this->assertSame(5, $chat->rating);
        $this->assertSame('Great support', $chat->feedback);
    }

    public function test_send_message_creates_message_linked_to_chat_from_agent(): void
    {
        $chat = LiveChat::factory()->create();

        $message = $this->service->sendMessage($chat, 'Hello there', true);

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertTrue($message->exists);
        $this->assertSame('Hello there', $message->content);
        $this->assertSame('agent', $message->sender);
        $this->assertSame($chat->id, $message->chat_id);
    }

    public function test_send_message_defaults_to_visitor_sender(): void
    {
        $chat = LiveChat::factory()->create();

        $message = $this->service->sendMessage($chat, 'I need help');

        $this->assertSame('visitor', $message->sender);
        $this->assertSame('I need help', $message->content);
    }

    public function test_get_active_chats_returns_only_active_chats(): void
    {
        LiveChat::factory()->create(['status' => LiveChat::STATUS_WAITING]);
        $active = LiveChat::factory()->create(['status' => LiveChat::STATUS_ACTIVE]);
        LiveChat::factory()->create(['status' => LiveChat::STATUS_ENDED]);

        $result = $this->service->getActiveChats();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame($active->id, $result->first()->id);
    }

    public function test_get_active_chats_filters_by_user_id(): void
    {
        $agent = User::factory()->create();
        $other = User::factory()->create();
        $mine = LiveChat::factory()->create([
            'status' => LiveChat::STATUS_ACTIVE,
            'user_id' => $agent->id,
        ]);
        LiveChat::factory()->create([
            'status' => LiveChat::STATUS_ACTIVE,
            'user_id' => $other->id,
        ]);

        $result = $this->service->getActiveChats($agent->id);

        $this->assertCount(1, $result);
        $this->assertSame($mine->id, $result->first()->id);
    }

    public function test_get_waiting_chats_returns_only_waiting_chats(): void
    {
        $waiting = LiveChat::factory()->create(['status' => LiveChat::STATUS_WAITING]);
        LiveChat::factory()->create(['status' => LiveChat::STATUS_ACTIVE]);
        LiveChat::factory()->create(['status' => LiveChat::STATUS_ENDED]);

        $result = $this->service->getWaitingChats();

        $this->assertCount(1, $result);
        $this->assertSame($waiting->id, $result->first()->id);
    }
}
