<?php

namespace Tests\Unit;

use App\Services\ImapService;
use App\Models\OAuthConfiguration;
use Tests\TestCase;
use Mockery;

class ImapServiceTest extends TestCase
{
    protected $imapService;
    protected $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imapService = new ImapService();
        
        $this->mockConfig = Mockery::mock(OAuthConfiguration::class);
        $this->mockConfig->id = 1;
        $this->mockConfig->client_id = 'test@example.com';
        $this->mockConfig->client_secret = 'test_password';
        $this->mockConfig->additional_settings = [
            'host' => 'imap.example.com',
            'port' => 993,
            'ssl' => true,
            'username' => 'test@example.com',
            'password' => 'test_password',
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testImapServiceCanBeInstantiated()
    {
        $this->assertInstanceOf(ImapService::class, $this->imapService);
    }

    public function testImapServiceRequiresConfiguration()
    {
        $configWithoutSettings = Mockery::mock(OAuthConfiguration::class);
        $configWithoutSettings->id = 1;
        $configWithoutSettings->client_id = 'test@example.com';
        $configWithoutSettings->client_secret = 'test_password';
        $configWithoutSettings->additional_settings = [];

        // Test that calling methods without proper host configuration will fail
        $this->expectException(\Exception::class);
        
        $this->imapService->getUnreadMessages($configWithoutSettings);
    }
}
