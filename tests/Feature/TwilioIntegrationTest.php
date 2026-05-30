<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TwilioIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twilioService = Mockery::mock(TwilioService::class);
        $this->app->instance(TwilioService::class, $this->twilioService);
    }

    public function test_initiate_call_service()
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->twilioService->shouldReceive('initiateCall')
            ->once()
            ->andReturn((object) ['sid' => 'CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX']);

        $this->twilioService->shouldReceive('logCall')
            ->once()
            ->andReturn(true);

        $call = app(TwilioService::class)->initiateCall($contact->phone_number);
        app(TwilioService::class)->logCall($call->sid, $contact->id, 'outbound', null, 'initiated');

        $this->assertEquals('CAXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', $call->sid);
    }

    public function test_send_sms_service()
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->twilioService->shouldReceive('sendSMS')
            ->once()
            ->with($contact->phone_number, 'Hello World')
            ->andReturn(true);

        $result = app(TwilioService::class)->sendSMS($contact->phone_number, 'Hello World');

        $this->assertTrue($result);
    }

    public function test_contact_has_phone_number()
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->assertEquals('+1234567890', $contact->phone_number);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
