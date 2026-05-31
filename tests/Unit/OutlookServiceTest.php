<?php

namespace Tests\Unit;

use App\Models\OAuthConfiguration;
use App\Services\OutlookService;
use Mockery;
use Tests\TestCase;

class OutlookServiceTest extends TestCase
{
    protected $outlookService;

    protected $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outlookService = new OutlookService;

        $this->mockConfig = new OAuthConfiguration;
        $this->mockConfig->forceFill([
            'id' => 1,
            'additional_settings' => [
                'access_token' => 'test_access_token',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_outlook_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(OutlookService::class, $this->outlookService);
    }

    public function test_get_unread_messages_requires_access_token(): void
    {
        $configWithoutToken = new OAuthConfiguration;
        $configWithoutToken->forceFill(['additional_settings' => []]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access token not found');

        $this->outlookService->getUnreadMessages($configWithoutToken);
    }
}
