<?php

namespace Tests\Unit;

use App\Models\OAuthConfiguration;
use App\Services\ImapService;
use Tests\TestCase;

class ImapServiceTest extends TestCase
{
    protected $imapService;

    protected $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imapService = new ImapService;

        $this->mockConfig = new OAuthConfiguration;
        $this->mockConfig->forceFill([
            'id' => 1,
            'client_id' => 'test@example.com',
            'client_secret' => 'test_password',
            'additional_settings' => [
                'host' => 'imap.example.com',
                'port' => 993,
                'ssl' => true,
                'username' => 'test@example.com',
                'password' => 'test_password',
            ],
        ]);
    }

    public function test_imap_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ImapService::class, $this->imapService);
    }

    public function test_imap_service_requires_configuration(): void
    {
        $configWithoutSettings = new OAuthConfiguration;
        $configWithoutSettings->forceFill([
            'id' => 1,
            'client_id' => 'test@example.com',
            'client_secret' => 'test_password',
            'additional_settings' => [],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('IMAP host is not configured');

        $this->imapService->getUnreadMessages($configWithoutSettings);
    }
}
