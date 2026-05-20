<?php

namespace App\Http\Controllers;

use App\Services\TwilioService;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class TwilioController extends Controller
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    public function initiateCall(Request $request)
    {
        $to = $request->input('to');
        $contactId = $request->input('contact_id');

        $call = $this->twilioService->initiateCall($to);

        if ($call) {
            $this->twilioService->logCall($call->sid, $contactId, 'outbound', null, 'initiated');
            return response()->json(['success' => true, 'call_sid' => $call->sid]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to initiate call'], 500);
    }

    public function handleOutboundCall(Request $request)
    {
        $response = new VoiceResponse();
        $dial = $response->dial('', ['callerId' => config('services.twilio.phone_number')]);
        $dial->number($request->input('To'));

        return response($response)->header('Content-Type', 'text/xml');
    }

    public function handleInboundCall(Request $request)
    {
        $response = new VoiceResponse();
        $response->say('Welcome to Liberu CRM. Please wait while we connect you to an agent.');
        $response->dial('', ['callerId' => $request->input('To')]);

        return response($response)->header('Content-Type', 'text/xml');
    }

    public function handleRecordingCallback(Request $request)
    {
        $callSid = $request->input('CallSid');
        $recordingUrl = $request->input('RecordingUrl');
        $duration = $request->input('RecordingDuration');

        // Here you would typically update your CallLog model with the recording information
        // For example:
        // CallLog::where('call_sid', $callSid)->update(['recording_url' => $recordingUrl, 'duration' => $duration]);

        return response('Recording processed', 200);
    }

    public function startRecording(Request $request)
    {
        $callSid = $request->input('call_sid');
        $recording = $this->twilioService->startCallRecording($callSid);

        if ($recording) {
            return response()->json(['success' => true, 'recording_sid' => $recording->sid]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to start recording'], 500);
    }

    public function stopRecording(Request $request)
    {
        $callSid = $request->input('call_sid');
        $success = $this->twilioService->stopCallRecording($callSid);

        if ($success) {
            return response()->json(['success' => true]);
        }


        return response()->json(['success' => false, 'message' => 'Failed to stop recording'], 500);
    }
}