<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

class TwilioWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $authToken = 'test_auth_token_12345';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.twilio.auth_token' => $this->authToken]);
    }

    protected function signRequest(string $url, array $params): string
    {
        $validator = new RequestValidator($this->authToken);

        return $validator->computeSignature($url, $params);
    }

    public function test_passes_with_valid_signature()
    {
        $params = ['To' => '+1234567890'];
        $url = route('twilio.twiml.outbound');
        $signature = $this->signRequest($url, $params);

        $response = $this->post($url, $params, ['X-Twilio-Signature' => $signature]);

        $response->assertStatus(200);
    }

    public function test_rejects_missing_signature()
    {
        $response = $this->post(route('twilio.twiml.outbound'), ['To' => '+1234567890']);

        $response->assertStatus(403);
    }

    public function test_rejects_bad_signature()
    {
        $response = $this->post(
            route('twilio.twiml.outbound'),
            ['To' => '+1234567890'],
            ['X-Twilio-Signature' => 'bad_signature']
        );

        $response->assertStatus(403);
    }

    public function test_recording_callback_passes_with_valid_signature()
    {
        $params = ['CallSid' => 'CA123', 'RecordingUrl' => 'https://recording.twilio.com/abc'];
        $url = route('twilio.recording.callback');
        $signature = $this->signRequest($url, $params);

        $response = $this->post($url, $params, ['X-Twilio-Signature' => $signature]);

        $response->assertStatus(200);
    }

    public function test_recording_callback_rejects_bad_signature()
    {
        $response = $this->post(
            route('twilio.recording.callback'),
            ['CallSid' => 'CA123'],
            ['X-Twilio-Signature' => 'bad']
        );

        $response->assertStatus(403);
    }

    public function test_skips_validation_when_token_not_configured()
    {
        config(['services.twilio.auth_token' => null]);

        $response = $this->post(route('twilio.twiml.outbound'), ['To' => '+1234567890']);

        $response->assertStatus(200);
    }
}
