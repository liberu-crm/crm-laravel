<?php

namespace Tests\Unit;

use App\Events\MessageReplySent;
use App\Events\NewMessageReceived;
use App\Models\OAuthConfiguration;
use App\Services\FacebookMessengerService;
use App\Services\GmailService;
use App\Services\ImapService;
use App\Services\OutlookService;
use App\Services\Pop3Service;
use App\Services\UnifiedHelpDeskService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class UnifiedHelpDeskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $unifiedHelpDeskService;

    protected $whatsAppService;

    protected $facebookService;

    protected $gmailService;

    protected $outlookService;

    protected $imapService;

    protected $pop3Service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->whatsAppService = Mockery::mock(WhatsAppBusinessService::class);
        $this->facebookService = Mockery::mock(FacebookMessengerService::class);
        $this->gmailService = Mockery::mock(GmailService::class);
        $this->outlookService = Mockery::mock(OutlookService::class);
        $this->imapService = Mockery::mock(ImapService::class);
        $this->pop3Service = Mockery::mock(Pop3Service::class);

        $this->unifiedHelpDeskService = new UnifiedHelpDeskService(
            $this->whatsAppService,
            $this->facebookService,
            $this->gmailService,
            $this->outlookService,
            $this->imapService,
            $this->pop3Service
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_unified_help_desk_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UnifiedHelpDeskService::class, $this->unifiedHelpDeskService);
    }

    public function test_get_all_messages_returns_collection(): void
    {
        // With RefreshDatabase, OAuthConfiguration table is empty, so no configs will be fetched
        $result = $this->unifiedHelpDeskService->getAllMessages(null, false);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_new_message_received_event_can_be_instantiated(): void
    {
        $message = [
            'id' => '123',
            'channel' => 'gmail',
            'from' => 'test@example.com',
            'content' => 'Test message',
            'timestamp' => now(),
        ];

        $event = new NewMessageReceived($message);

        $this->assertInstanceOf(NewMessageReceived::class, $event);
        $this->assertEquals($message, $event->message);
    }

    public function test_message_reply_sent_event_can_be_instantiated(): void
    {
        $event = new MessageReplySent('msg-123', 'Reply content', 'gmail', 1);

        $this->assertInstanceOf(MessageReplySent::class, $event);
        $this->assertEquals('msg-123', $event->messageId);
        $this->assertEquals('Reply content', $event->content);
        $this->assertEquals('gmail', $event->channel);
        $this->assertEquals(1, $event->accountId);
    }

    public function test_calculate_priority_detects_urgent_in_message_key(): void
    {
        $method = new ReflectionMethod(UnifiedHelpDeskService::class, 'calculatePriority');

        $message = ['message' => 'This is urgent please help'];
        $priority = $method->invoke($this->unifiedHelpDeskService, $message);

        $this->assertEquals('high', $priority);
    }

    public function test_calculate_priority_detects_urgent_in_content_key(): void
    {
        $method = new ReflectionMethod(UnifiedHelpDeskService::class, 'calculatePriority');

        $message = ['content' => 'This is an emergency situation'];
        $priority = $method->invoke($this->unifiedHelpDeskService, $message);

        $this->assertEquals('high', $priority);
    }

    public function test_calculate_priority_returns_normal_for_regular_messages(): void
    {
        $method = new ReflectionMethod(UnifiedHelpDeskService::class, 'calculatePriority');

        $message = ['content' => 'Hello, I have a general question.'];
        $priority = $method->invoke($this->unifiedHelpDeskService, $message);

        $this->assertEquals('normal', $priority);
    }

    public function test_calculate_priority_handles_empty_message(): void
    {
        $method = new ReflectionMethod(UnifiedHelpDeskService::class, 'calculatePriority');

        $priority = $method->invoke($this->unifiedHelpDeskService, []);

        $this->assertEquals('normal', $priority);
    }
}
