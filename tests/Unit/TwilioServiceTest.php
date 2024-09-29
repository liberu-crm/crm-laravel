<?php


namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TwilioService;
use Twilio\Rest\Client;
use Mockery;
use Twilio\Rest\Api\V2010\Account\CallList;
use Twilio\Rest\Api\V2010\Account\CallInstance;

class TwilioServiceTest extends TestCase
{
    protected $twilioService;
    protected $mockTwilioClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTwilioClient = Mockery::mock(Client::class);
        $this->twilioService = new TwilioService();
        $this->twilioService->setClient($this->mockTwilioClient);
    }

    public function testSendSMS()
    {
        $to = '+1234567890';
        $message = 'Test message';

        $this->mockTwilioClient->shouldReceive('messages->create')
            ->once()
            ->with($to, [
                'from' => config('services.twilio.phone_number'),
                'body' => $message,
            ])
            ->andReturn(true);

        $result = $this->twilioService->sendSMS($to, $message);
        $this->assertTrue($result);
    }

    public function testMakeCall()
    {
        $to = '+1234567890';
        $url = 'http://example.com/twiml';

        $this->mockTwilioClient->shouldReceive('calls->create')
            ->once()
            ->with($to, config('services.twilio.phone_number'), [
                'url' => $url,
            ])
            ->andReturn(true);

        $result = $this->twilioService->makeCall($to, $url);
        $this->assertTrue($result);
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
        $mockCallInstance = Mockery::mock(CallInstance::class);

        $this->mockTwilioClient->shouldReceive('calls->create')
            ->once()
            ->with($to, config('services.twilio.phone_number'), [
                'url' => route('twilio.twiml.outbound'),
            ])
            ->andReturn($mockCallInstance);

        $result = $this->twilioService->initiateCall($to);
        $this->assertInstanceOf(CallInstance::class, $result);
    }

    public function testStartCallRecording()
    {
        $callSid = 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $mockRecordingInstance = Mockery::mock('Twilio\Rest\Api\V2010\Account\Call\RecordingInstance');

        $this->mockTwilioClient->calls = Mockery::mock('Twilio\Rest\Api\V2010\Account\CallContext');
        $this->mockTwilioClient->calls->shouldReceive('recordings->create')
            ->once()
            ->with(['recordingStatusCallback' => route('twilio.recording.callback')])
            ->andReturn($mockRecordingInstance);

        $result = $this->twilioService->startCallRecording($callSid);
        $this->assertInstanceOf('Twilio\Rest\Api\V2010\Account\Call\RecordingInstance', $result);
    }

    public function testStopCallRecording()
    {
        $callSid = 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $mockRecordingList = Mockery::mock('Twilio\Rest\Api\V2010\Account\Call\RecordingList');
        $mockRecordingContext = Mockery::mock('Twilio\Rest\Api\V2010\Account\Call\RecordingContext');

        $this->mockTwilioClient->calls = Mockery::mock('Twilio\Rest\Api\V2010\Account\CallContext');
        $this->mockTwilioClient->calls->shouldReceive('recordings->read')
            ->once()
            ->with(['status' => 'in-progress'])
            ->andReturn([$mockRecordingContext]);

        $mockRecordingContext->shouldReceive('update')
            ->once()
            ->with(['status' => 'stopped'])
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