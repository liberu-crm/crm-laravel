<?php

namespace Tests\Unit\Services;

use App\Services\MailChimpService;
use Tests\TestCase;
use Mockery;
use MailchimpMarketing\ApiClient;

class MailChimpServiceTest extends TestCase
{
    protected $mailChimpService;
    protected $mockApiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApiClient = Mockery::mock(ApiClient::class);
        $this->mailChimpService = new MailChimpService();
        $this->mailChimpService->setClient($this->mockApiClient);
    }

    public function testMailChimpServiceCanBeInstantiated()
    {
        $this->assertInstanceOf(MailChimpService::class, $this->mailChimpService);
    }

    public function testGetListsMethodCanBeCalled()
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