<?php

namespace App\Services;

use Twilio\Rest\Client;

class PowerDialerService
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

    public function startBulkCalls($contacts, $message)
    {
        $results = [];
        foreach ($contacts as $contact) {
            $call = $this->client->calls->create(
                $contact->phone_number,
                $this->from_number,
                [
                    'url' => route('power-dialer.twiml', ['message' => $message]),
                    'statusCallback' => route('power-dialer.status-callback'),
                    'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                ]
            );
            $results[] = [
                'contact_id' => $contact->id,
                'call_sid' => $call->sid,
                'status' => $call->status,
            ];
        }
        return $results;
    }

    public function getCallStatus($callSid)
    {
        return $this->client->calls($callSid)->fetch();
    }
}