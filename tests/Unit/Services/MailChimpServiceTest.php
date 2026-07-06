<?php

namespace Tests\Unit\Services;

use App\Services\MailChimpService;
use MailchimpMarketing\ApiClient;
use Mockery;
use Tests\TestCase;

class MailChimpServiceTest extends TestCase
{
    protected $mailChimpService;

    protected $mockApiClient;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mailchimp.api_key', 'test-key');
        config()->set('services.mailchimp.server_prefix', 'us1');

        $this->mockApiClient = Mockery::mock(ApiClient::class);
        $this->mailChimpService = new MailChimpService;
        $this->mailChimpService->setClient($this->mockApiClient);
    }

    public function test_mail_chimp_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(MailChimpService::class, $this->mailChimpService);
    }

    public function test_is_configured_returns_true_when_configured(): void
    {
        $this->assertTrue($this->mailChimpService->isConfigured());
    }

    public function test_is_configured_returns_false_when_missing_api_key(): void
    {
        config()->set('services.mailchimp.api_key', '');

        $service = new MailChimpService;

        $this->assertFalse($service->isConfigured());
    }

    public function test_is_configured_returns_false_when_missing_server_prefix(): void
    {
        config()->set('services.mailchimp.server_prefix', '');

        $service = new MailChimpService;

        $this->assertFalse($service->isConfigured());
    }

    public function test_get_lists_returns_empty_array_when_not_configured(): void
    {
        config()->set('services.mailchimp.api_key', '');

        $service = new MailChimpService;

        $this->assertSame([], $service->getLists());
    }

    public function test_get_lists_method_can_be_called(): void
    {
        $mockResponse = (object) ['lists' => [
            (object) ['id' => '1', 'name' => 'List 1'],
            (object) ['id' => '2', 'name' => 'List 2'],
        ]];

        $this->mockApiClient->lists = Mockery::mock();
        $this->mockApiClient->lists->shouldReceive('getAllLists')->once()->andReturn($mockResponse);

        $result = $this->mailChimpService->getLists();

        $this->assertEquals(['1' => 'List 1', '2' => 'List 2'], $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
