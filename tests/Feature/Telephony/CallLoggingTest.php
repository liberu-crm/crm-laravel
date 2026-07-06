<?php

namespace Tests\Feature\Telephony;

use App\Models\CallLog;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Twilio\Rest\Client;

class CallLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_call_persists_a_call_log(): void
    {
        $callLog = (new TwilioService)->logCall('CA123', 42, 'outbound', null, 'initiated');

        $this->assertInstanceOf(CallLog::class, $callLog);
        $this->assertDatabaseHas('call_logs', [
            'call_sid' => 'CA123',
            'contact_id' => 42,
            'direction' => 'outbound',
            'duration' => null,
            'status' => 'initiated',
        ]);
    }

    public function test_end_call_updates_status_to_completed(): void
    {
        CallLog::create([
            'call_sid' => 'CA999',
            'contact_id' => null,
            'direction' => 'outbound',
            'duration' => null,
            'status' => 'initiated',
        ]);

        // Mirror the client-mocking approach used for stopCallRecording:
        // calls($sid)->update([...]) hangs the call up via the Twilio client.
        $callContext = Mockery::mock();
        $callContext->shouldReceive('update')
            ->once()
            ->with(['status' => 'completed'])
            ->andReturn(Mockery::mock());

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('calls')
            ->once()
            ->with('CA999')
            ->andReturn($callContext);

        $service = new TwilioService;
        $service->setClient($client);

        $result = $service->endCall('CA999');

        $this->assertTrue($result);
        $this->assertDatabaseHas('call_logs', [
            'call_sid' => 'CA999',
            'status' => 'completed',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
