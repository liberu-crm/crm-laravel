<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Contact;
use App\Services\TwilioService;
use Mockery;

class TwilioIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twilioService = Mockery::mock(TwilioService::class);
        $this->app->instance(TwilioService::class, $this->twilioService);
    }

    public function testInitiateCall()
    {
        $contact = Contact::factory()->create();

        $this->twilioService->shouldReceive('initiateCall')
            ->once()
            ->with($contact->phone)
            ->andReturn((object)['sid' => 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']);

        $this->twilioService->shouldReceive('logCall')
            ->once()
            ->with('CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', $contact->id, 'outbound', null, 'initiated')
            ->andReturn(true);

        $response = $this->postJson('/twilio/initiate-call', [
            'to' => $contact->phone,
            'contact_id' => $contact->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'call_sid' => 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]);
    }

    public function testHandleOutboundCall()
    {
        $response = $this->post('/twilio/twiml/outbound', ['To' => '+1234567890']);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSee('<Response><Dial callerId="' . config('services.twilio.phone_number') . '"><Number>+1234567890</Number></Dial></Response>', false);
    }

    public function testHandleInboundCall()
    {
        $response = $this->post('/twilio/twiml/inbound', ['To' => '+1234567890']);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSee('<Response><Say>Welcome to Liberu CRM. Please wait while we connect you to an agent.</Say><Dial callerId="+1234567890"></Dial></Response>', false);
    }

    public function testStartRecording()
    {
        $this->twilioService->shouldReceive('startCallRecording')
            ->once()
            ->with('CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX')
            ->andReturn((object)['sid' => 'REXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']);

        $response = $this->postJson('/twilio/start-recording', [
            'call_sid' => 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'recording_sid' => 'REXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
            ]);
    }

    public function testStopRecording()
    {
        $this->twilioService->shouldReceive('stopCallRecording')
            ->once()
            ->with('CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX')
            ->andReturn(true);

        $response = $this->postJson('/twilio/stop-recording', [
            'call_sid' => 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}