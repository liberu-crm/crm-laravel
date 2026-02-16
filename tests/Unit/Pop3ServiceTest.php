<?php

namespace Tests\Unit;

use App\Services\Pop3Service;
use App\Models\OAuthConfiguration;
use Tests\TestCase;
use Mockery;

class Pop3ServiceTest extends TestCase
{
    protected $pop3Service;
    protected $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pop3Service = new Pop3Service();
        
        $this->mockConfig = Mockery::mock(OAuthConfiguration::class);
        $this->mockConfig->id = 1;
        $this->mockConfig->client_id = 'test@example.com';
        $this->mockConfig->client_secret = 'test_password';
        $this->mockConfig->additional_settings = [
            'host' => 'pop3.example.com',
            'port' => 110,
            'ssl' => false,
            'username' => 'test@example.com',
            'password' => 'test_password',
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testPop3ServiceCanBeInstantiated()
    {
        $this->assertInstanceOf(Pop3Service::class, $this->pop3Service);
    }

    public function testPop3ServiceRequiresConfiguration()
    {
        $configWithoutSettings = Mockery::mock(OAuthConfiguration::class);
        $configWithoutSettings->id = 1;
        $configWithoutSettings->client_id = 'test@example.com';
        $configWithoutSettings->client_secret = 'test_password';
        $configWithoutSettings->additional_settings = [];

        // Test that calling methods without proper host configuration will fail
        $this->expectException(\Exception::class);
        
        $this->pop3Service->getUnreadMessages($configWithoutSettings);
    }
}
