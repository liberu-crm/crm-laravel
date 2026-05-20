<?php

namespace Tests\Unit;

use App\Services\OutlookService;
use App\Models\OAuthConfiguration;
use Tests\TestCase;
use Mockery;

class OutlookServiceTest extends TestCase
{
    protected $outlookService;
    protected $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outlookService = new OutlookService();
        
        $this->mockConfig = new OAuthConfiguration();
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

    public function testOutlookServiceCanBeInstantiated()
    {
        $this->assertInstanceOf(OutlookService::class, $this->outlookService);
    }

    public function testGetUnreadMessagesRequiresAccessToken()
    {
        $configWithoutToken = new OAuthConfiguration();
        $configWithoutToken->forceFill(['additional_settings' => []]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access token not found');

        $this->outlookService->getUnreadMessages($configWithoutToken);
    }
}
