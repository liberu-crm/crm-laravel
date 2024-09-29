<?php

namespace Tests\Unit;

use App\Services\FacebookMessengerService;
use Facebook\Facebook;
use Mockery;
use Tests\TestCase;

class FacebookMessengerServiceTest extends TestCase
{
    protected $facebookMock;
    protected $facebookMessengerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facebookMock = Mockery::mock(Facebook::class);
        $this->app->instance(Facebook::class, $this->facebookMock);

        $this->facebookMessengerService = new FacebookMessengerService();
    }

    public function testGetUnreadMessages()
    {
        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getGraphEdge')->andReturn([
            (object) [
                'messages' => [
                    (object) [
                        'id' => '123',
                        'from' => ['name' => 'John Doe'],
                        'message' => 'Hello',
                        'created_time' => '2023-04-20T12:00:00+0000',
                    ],
                ],
            ],
        ]);

        $this->facebookMock->shouldReceive('get')->andReturn($mockResponse);

        $unreadMessages = $this->facebookMessengerService->getUnreadMessages();

        $this->assertIsArray($unreadMessages);
        $this->assertCount(1, $unreadMessages);
        $this->assertEquals('123', $unreadMessages[0]['id']);
        $this->assertEquals('John Doe', $unreadMessages[0]['from']);
        $this->assertEquals('Hello', $unreadMessages[0]['message']);
    }

    public function testGetMessage()
    {
        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getGraphNode')->andReturn(
            (object) [
                'id' => '123',
                'from' => ['name' => 'John Doe'],
                'message' => 'Hello',
                'created_time' => '2023-04-20T12:00:00+0000',
            ]
        );

        $this->facebookMock->shouldReceive('get')->andReturn($mockResponse);

        $message = $this->facebookMessengerService->getMessage('123');

        $this->assertIsArray($message);
        $this->assertEquals('123', $message['id']);
        $this->assertEquals('John Doe', $message['from']);
        $this->assertEquals('Hello', $message['message']);
    }

    public function testSendReply()
    {
        $mockResponse = Mockery::mock();
        $mockResponse->shouldReceive('getGraphNode')->andReturn(
            (object) [
                'message_id' => '456',
            ]
        );

        $this->facebookMock->shouldReceive('post')->andReturn($mockResponse);

        $response = $this->facebookMessengerService->sendReply('123', 'Hello back!');

        $this->assertIsObject($response);
        $this->assertEquals('456', $response->getField('message_id'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}