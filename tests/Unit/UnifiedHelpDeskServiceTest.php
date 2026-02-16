<?php

namespace Tests\Unit;

use App\Services\UnifiedHelpDeskService;
use App\Services\WhatsAppBusinessService;
use App\Services\FacebookMessengerService;
use App\Services\GmailService;
use App\Services\OutlookService;
use App\Services\ImapService;
use App\Services\Pop3Service;
use App\Models\OAuthConfiguration;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Collection;

class UnifiedHelpDeskServiceTest extends TestCase
{
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

    public function testUnifiedHelpDeskServiceCanBeInstantiated()
    {
        $this->assertInstanceOf(UnifiedHelpDeskService::class, $this->unifiedHelpDeskService);
    }

    public function testGetAllMessagesReturnsCollection()
    {
        // Mock OAuthConfiguration query
        $mockConfig = Mockery::mock('alias:' . OAuthConfiguration::class);
        $mockConfig->shouldReceive('where')->andReturnSelf();
        $mockConfig->shouldReceive('get')->andReturn(collect());

        $result = $this->unifiedHelpDeskService->getAllMessages(null, false);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
