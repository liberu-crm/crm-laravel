<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TwilioService;
use Twilio\Rest\Client;
use Mockery;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Api\V2010\Account\CallList;
use Twilio\Rest\Api\V2010\Account\CallInstance;

class TwilioServiceTest extends TestCase
{
    protected $twilioService;
    protected $mockTwilioClient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.twilio.phone_number' => '+15551234567']);
        $this->mockTwilioClient = Mockery::mock(Client::class);
        $this->mockTwilioClient->shouldReceive('getAccountSid')
            ->andReturn('ACtest1234567890123456789012345678');
        $this->twilioService = new TwilioService();
        $this->twilioService->setClient($this->mockTwilioClient);
    }

    public function testSendSMS()
    {
        $to = '+1234567890';
        $from = config('services.twilio.phone_number');
        $message = 'Test message';

        $mockMessages = Mockery::mock();
        $mockMessages->shouldReceive('create')
            ->once()
            ->with($to, ['from' => $from, 'body' => $message])
            ->andReturn(true);
        $this->mockTwilioClient->messages = $mockMessages;

        $result = $this->twilioService->sendSMS($to, $message);
        $this->assertTrue($result);
    }

    public function testSendSMSWithRetry()
    {
        $to = '+1234567890';
        $message = 'Test message';

        $mockMessages = Mockery::mock();
        $mockMessages->shouldReceive('create')
            ->once()
            ->andThrow(new TwilioException('SMS failed'));
        $mockMessages->shouldReceive('create')
            ->once()
            ->andReturn(true);
        $this->mockTwilioClient->messages = $mockMessages;

        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->once();

        $result = $this->twilioService->sendSMS($to, $message);
        $this->assertTrue($result);
    }

    public function testMakeCall()
    {
        $to = '+1234567890';
        $from = config('services.twilio.phone_number');
        $url = 'http://example.com/twiml';

        $mockCallList = Mockery::mock();
        $mockCallList->shouldReceive('create')
            ->once()
            ->with($to, $from, ['url' => $url])
            ->andReturn(true);
        $this->mockTwilioClient->calls = $mockCallList;

        $result = $this->twilioService->makeCall($to, $url);
        $this->assertTrue($result);
    }

    public function testMakeCallWithRetry()
    {
        $to = '+1234567890';
        $url = 'http://example.com/twiml';

        $mockCallList = Mockery::mock();
        $mockCallList->shouldReceive('create')
            ->times(3)
            ->andThrow(new TwilioException('Call failed'));
        $this->mockTwilioClient->calls = $mockCallList;

        Log::shouldReceive('warning')->times(3);
        Log::shouldReceive('error')->once();

        $this->expectException(TwilioException::class);
        $this->twilioService->makeCall($to, $url);
    }

    public function testHandleTwilioApiException()
    {
        $to = '+1234567890';
        $message = 'Test message';

        $mockMessages = Mockery::mock();
        $mockMessages->shouldReceive('create')
            ->times(3)
            ->andThrow(new TwilioException('API Error'));
        $this->mockTwilioClient->messages = $mockMessages;

        Log::shouldReceive('warning')->times(3);
        Log::shouldReceive('error')->once();

        $this->expectException(TwilioException::class);
        $this->twilioService->sendSMS($to, $message);
    }

    public function testGetCallLogs()
    {
        $mockCallList = Mockery::mock(CallList::class);
        $mockCallList->shouldReceive('read')
            ->once()
            ->with([])
            ->andReturn([]);

        $this->mockTwilioClient->calls = $mockCallList;

        $result = $this->twilioService->getCallLogs();
        $this->assertIsArray($result);
    }

    public function testGetCallDetails()
    {
        $callSid = 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $mockCallInstance = Mockery::mock(CallInstance::class);

        $this->mockTwilioClient->shouldReceive('calls')
            ->once()
            ->with($callSid)
            ->andReturn($mockCallInstance);

        $mockCallInstance->shouldReceive('fetch')
            ->once()
            ->andReturn($mockCallInstance);

        $result = $this->twilioService->getCallDetails($callSid);
        $this->assertInstanceOf(CallInstance::class, $result);
    }

    public function testInitiateCall()
    {
        $to = '+1234567890';
        $from = config('services.twilio.phone_number');
        $mockCallInstance = Mockery::mock(CallInstance::class);

        $mockCallList = Mockery::mock();
        $mockCallList->shouldReceive('create')
            ->once()
            ->with($to, $from, ['url' => route('twilio.twiml.outbound')])
            ->andReturn($mockCallInstance);
        $this->mockTwilioClient->calls = $mockCallList;

        $result = $this->twilioService->initiateCall($to);
        $this->assertInstanceOf(CallInstance::class, $result);
    }

    public function testStartCallRecording()
    {
        $callSid = 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $mockRecordingInstance = Mockery::mock('Twilio\Rest\Api\V2010\Account\Call\RecordingInstance');

        $mockCallContext = Mockery::mock('Twilio\Rest\Api\V2010\Account\CallContext');
        $this->mockTwilioClient->shouldReceive('calls')
            ->once()
            ->with($callSid)
            ->andReturn($mockCallContext);

        $mockRecordingList = Mockery::mock();
        $mockRecordingList->shouldReceive('create')
            ->once()
            ->with(['recordingStatusCallback' => route('twilio.recording.callback')])
            ->andReturn($mockRecordingInstance);
        $mockCallContext->recordings = $mockRecordingList;

        $result = $this->twilioService->startCallRecording($callSid);
        $this->assertInstanceOf('Twilio\Rest\Api\V2010\Account\Call\RecordingInstance', $result);
    }

    public function testStopCallRecording()
    {
        $callSid = 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $mockRecordingContext = Mockery::mock('Twilio\Rest\Api\V2010\Account\Call\RecordingContext');

        $mockCallContext = Mockery::mock('Twilio\Rest\Api\V2010\Account\CallContext');
        $this->mockTwilioClient->shouldReceive('calls')
            ->once()
            ->with($callSid)
            ->andReturn($mockCallContext);

        $mockRecordingList = Mockery::mock();
        $mockRecordingList->shouldReceive('read')
            ->once()
            ->with(['status' => 'in-progress'])
            ->andReturn([$mockRecordingContext]);
        $mockCallContext->recordings = $mockRecordingList;

        $mockRecordingContext->shouldReceive('update')
            ->once()
            ->with('stopped')
            ->andReturn(true);

        $result = $this->twilioService->stopCallRecording($callSid);
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}