<?php

namespace Tests\Unit;

use App\Models\OAuthConfiguration;
use App\Services\Pop3Service;
use Mockery;
use Tests\TestCase;

class Pop3ServiceTest extends TestCase
{
    protected $pop3Service;

    protected $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pop3Service = new Pop3Service;

        $this->mockConfig = new OAuthConfiguration;
        $this->mockConfig->forceFill([
            'id' => 1,
            'client_id' => 'test@example.com',
            'client_secret' => 'test_password',
            'additional_settings' => [
                'host' => 'pop3.example.com',
                'port' => 110,
                'ssl' => false,
                'username' => 'test@example.com',
                'password' => 'test_password',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pop3_service_can_be_instantiated()
    {
        $this->assertInstanceOf(Pop3Service::class, $this->pop3Service);
    }

    public function test_pop3_service_requires_configuration()
    {
        $configWithoutSettings = new OAuthConfiguration;
        $configWithoutSettings->forceFill([
            'id' => 1,
            'client_id' => 'test@example.com',
            'client_secret' => 'test_password',
            'additional_settings' => [],
        ]);

        // Test that calling methods without proper host configuration will fail
        $this->expectException(\Exception::class);

        $this->pop3Service->getUnreadMessages($configWithoutSettings);
    }
}
