<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected int $maxRetries = 3;

    public function __construct()
    {
        // Client is instantiated lazily or injected via setClient()
    }

    public function setClient($client): void
    {
        $this->client = $client;
    }

    protected function getClient()
    {
        if ($this->client === null) {
            $accountSid = config('services.twilio.account_sid');
            $authToken  = config('services.twilio.auth_token');
            $this->client = new \Twilio\Rest\Client($accountSid, $authToken);
        }

        return $this->client;
    }

    public function sendSMS(string $to, string $message): bool
    {
        $attempts = 0;
        while ($attempts < $this->maxRetries) {
            try {
                $this->getClient()->messages->create($to, [
                    'from' => config('services.twilio.phone_number'),
                    'body' => $message,
                ]);

                if ($attempts > 0) {
                    Log::info("SMS sent successfully after {$attempts} retries.");
                }

                return true;
            } catch (\Twilio\Exceptions\TwilioException $e) {
                $attempts++;
                Log::warning("SMS attempt {$attempts} failed: " . $e->getMessage());

                if ($attempts >= $this->maxRetries) {
                    Log::error("Failed to send SMS after {$this->maxRetries} attempts: " . $e->getMessage());
                    throw $e;
                }
            }
        }

        return false;
    }

    public function makeCall(string $to, string $url): bool
    {
        $attempts = 0;
        while ($attempts < $this->maxRetries) {
            try {
                $this->getClient()->calls->create($to, config('services.twilio.phone_number'), [
                    'url' => $url,
                ]);

                return true;
            } catch (\Twilio\Exceptions\TwilioException $e) {
                $attempts++;
                Log::warning("Call attempt {$attempts} failed: " . $e->getMessage());

                if ($attempts >= $this->maxRetries) {
                    Log::error("Failed to make call after {$this->maxRetries} attempts: " . $e->getMessage());
                    throw $e;
                }
            }
        }

        return false;
    }

    public function getCallLogs(array $filters = []): array
    {
        return $this->getClient()->calls->read($filters);
    }

    public function getCallDetails(string $callSid)
    {
        return $this->getClient()->calls($callSid)->fetch();
    }

    public function initiateCall(string $to)
    {
        return $this->getClient()->calls->create($to, config('services.twilio.phone_number'), [
            'url' => route('twilio.twiml.outbound'),
        ]);
    }

    public function startCallRecording(string $callSid)
    {
        return $this->getClient()->calls($callSid)->recordings->create([
            'recordingStatusCallback' => route('twilio.recording.callback'),
        ]);
    }

    public function stopCallRecording(string $callSid): bool
    {
        $recordings = $this->getClient()->calls($callSid)->recordings->read(['status' => 'in-progress']);

        foreach ($recordings as $recording) {
            $recording->update(['status' => 'stopped']);
        }

        return true;
    }

    public function sendBulkSMS(array $recipients, string $message): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->sendSMS($recipient, $message);
        }
        return $results;
    }

    public function initiateBulkCalls(array $recipients, string $url): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->makeCall($recipient, $url);
        }
        return $results;
    }
}
