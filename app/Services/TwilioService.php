<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    // ... (existing code remains unchanged)

    public function sendBulkSMS($recipients, $message)
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->sendSMS($recipient, $message);
        }
        return $results;
    }

    public function initiateBulkCalls($recipients, $url)
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->makeCall($recipient, $url);
        }
        return $results;
    }

    // ... (rest of the methods remain unchanged)
}