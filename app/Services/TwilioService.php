<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected $from_number;
    protected $max_retries = 3;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->from_number = config('services.twilio.phone_number');
    }

    public function sendSMS($to, $message)
    {
        $attempts = 0;
        while ($attempts < $this->max_retries) {
            try {
                $result = $this->client->messages->create($to, [
                    'from' => $this->from_number,
                    'body' => $message,
                ]);
                Log::info("SMS sent successfully to {$to}");
                return $result;
            } catch (TwilioException $e) {
                $attempts++;
                Log::warning("Twilio SMS Error (Attempt {$attempts}): " . $e->getMessage());
                if ($attempts >= $this->max_retries) {
                    Log::error("Failed to send SMS to {$to} after {$this->max_retries} attempts");
                    throw $e;
                }
                sleep(2 ** $attempts); // Exponential backoff
            }
        }
    }

    public function makeCall($to, $url)
    {
        $attempts = 0;
        while ($attempts < $this->max_retries) {
            try {
                $result = $this->client->calls->create($to, $this->from_number, [
                    'url' => $url,
                ]);
                Log::info("Call initiated successfully to {$to}");
                return $result;
            } catch (TwilioException $e) {
                $attempts++;
                Log::warning("Twilio Call Error (Attempt {$attempts}): " . $e->getMessage());
                if ($attempts >= $this->max_retries) {
                    Log::error("Failed to initiate call to {$to} after {$this->max_retries} attempts");
                    throw $e;
                }
                sleep(2 ** $attempts); // Exponential backoff
            }
        }
    }

    // ... (rest of the methods remain unchanged)
}

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class TwilioService
{
    protected $client;
    protected $from_number;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->from_number = config('services.twilio.phone_number');
    }

    public function sendSMS($to, $message)
    {
        try {
            return $this->client->messages->create($to, [
                'from' => $this->from_number,
                'body' => $message,
            ]);
        } catch (TwilioException $e) {
            // Log the error and return null or throw a custom exception
            \Log::error('Twilio SMS Error: ' . $e->getMessage());
            return null;
        }
    }

    public function sendBulkSMS($recipients, $message)
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->sendSMS($recipient, $message);
        }
        return $results;
    }

    public function makeCall($to, $url)
    {
        try {
            return $this->client->calls->create($to, $this->from_number, [
                'url' => $url,
            ]);
        } catch (TwilioException $e) {
            \Log::error('Twilio Call Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getCallLogs($startDate = null, $endDate = null)
    {
        $params = [];
        if ($startDate) {
            $params['startTime'] = $startDate;
        }
        if ($endDate) {
            $params['endTime'] = $endDate;
        }

        try {
            return $this->client->calls->read($params);
        } catch (TwilioException $e) {
            \Log::error('Twilio Call Logs Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getCallDetails($callSid)
    {
        try {
            return $this->client->calls($callSid)->fetch();
        } catch (TwilioException $e) {
            \Log::error('Twilio Call Details Error: ' . $e->getMessage());
            return null;
        }
    }

    public function initiateCall($to, $from = null)
    {
        $from = $from ?: $this->from_number;
        try {
            return $this->client->calls->create($to, $from, [
                'url' => route('twilio.twiml.outbound'),
            ]);
        } catch (TwilioException $e) {
            \Log::error('Twilio Initiate Call Error: ' . $e->getMessage());
            return null;
        }
    }

    public function logCall($callSid, $contactId, $direction, $duration, $status)
    {
        // Assuming we have a CallLog model
        return \App\Models\CallLog::create([
            'call_sid' => $callSid,
            'contact_id' => $contactId,
            'direction' => $direction,
            'duration' => $duration,
            'status' => $status,
        ]);
    }

    public function startCallRecording($callSid)
    {
        try {
            return $this->client->calls($callSid)
                ->recordings
                ->create(['recordingStatusCallback' => route('twilio.recording.callback')]);
        } catch (TwilioException $e) {
            \Log::error('Twilio Start Recording Error: ' . $e->getMessage());
            return null;
        }
    }

    public function stopCallRecording($callSid)
    {
        try {
            $recordings = $this->client->calls($callSid)->recordings->read(['status' => 'in-progress']);
            foreach ($recordings as $recording) {
                $this->client->calls($callSid)
                    ->recordings($recording->sid)
                    ->update(['status' => 'stopped']);
            }
            return true;
        } catch (TwilioException $e) {
            \Log::error('Twilio Stop Recording Error: ' . $e->getMessage());
            return false;
        }
    }
}