<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\TwilioService;

class CallManager extends Component
{
    public $callSid;
    public $contactId;
    public $status = 'idle';
    public $recordingStatus = 'not_recording';
    public $notes = '';

    protected $listeners = ['callInitiated', 'updateCallStatus'];

    public function mount($contactId)
    {
        $this->contactId = $contactId;
    }

    public function initiateCall()
    {
        $twilioService = app(TwilioService::class);
        $contact = \App\Models\Contact::find($this->contactId);
        
        if (!$contact) {
            $this->addError('call', 'Contact not found');
            return;
        }

        $call = $twilioService->initiateCall($contact->phone);
        
        if ($call) {
            $this->callSid = $call->sid;
            $this->status = 'initiating';
            $twilioService->logCall($call->sid, $this->contactId, 'outbound', null, 'initiated');
            $this->emit('callInitiated', $call->sid);
        } else {
            $this->addError('call', 'Failed to initiate call');
        }
    }

    public function startRecording()
    {
        if (!$this->callSid) {
            $this->addError('recording', 'No active call to record');
            return;
        }

        $twilioService = app(TwilioService::class);
        $recording = $twilioService->startCallRecording($this->callSid);

        if ($recording) {
            $this->recordingStatus = 'recording';
        } else {
            $this->addError('recording', 'Failed to start recording');
        }
    }

    public function stopRecording()
    {
        if (!$this->callSid || $this->recordingStatus !== 'recording') {
            $this->addError('recording', 'No active recording to stop');
            return;
        }

        $twilioService = app(TwilioService::class);
        $success = $twilioService->stopCallRecording($this->callSid);

        if ($success) {
            $this->recordingStatus = 'stopped';

        } else {
            $this->addError('recording', 'Failed to stop recording');
        }
    }

    public function endCall()
    {
        if (!$this->callSid) {
            $this->addError('call', 'No active call to end');
            return;
        }

        $twilioService = app(TwilioService::class);
        // Implement a method in TwilioService to end the call
        $success = $twilioService->endCall($this->callSid);

        if ($success) {
            $this->status = 'ended';
            $this->callSid = null;
        } else {
            $this->addError('call', 'Failed to end call');
        }
    }

    public function saveNotes()
    {
        // Save notes to the database
        \App\Models\CallLog::where('call_sid', $this->callSid)->update(['notes' => $this->notes]);
        $this->emit('notesSaved');
    }

    public function updateCallStatus($status)
    {
        $this->status = $status;
    }

    public function render()
    {
        return view('livewire.call-manager');
    }
}