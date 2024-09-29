<?php

namespace App\Services;

use Twilio\Rest\Client;

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
        return $this->client->messages->create($to, [
            'from' => $this->from_number,
            'body' => $message,
        ]);
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
        return $this->client->calls->create($to, $this->from_number, [
            'url' => $url,
        ]);
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

        return $this->client->calls->read($params);
    }

    public function getCallDetails($callSid)
    {
        return $this->client->calls($callSid)->fetch();
    }
}