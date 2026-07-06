<?php

namespace App\Livewire;

use App\Models\CallLog;
use App\Models\Contact;
use App\Services\TwilioService;
use Livewire\Attributes\On;
use Livewire\Component;

class CallManager extends Component
{
    public ?string $callSid = null;

    public int $contactId;

    public string $status = 'idle';

    public string $recordingStatus = 'not_recording';

    public string $notes = '';

    public function mount(int $contactId): void
    {
        $this->contactId = $contactId;
    }

    public function initiateCall(): void
    {
        $twilioService = app(TwilioService::class);
        $contact = Contact::find($this->contactId);

        if (! $contact) {
            $this->addError('call', 'Contact not found');
            return;
        }

        $call = $twilioService->initiateCall($contact->phone_number);

        if ($call) {
            $this->callSid = $call->sid;
            $this->status = 'initiating';
            $twilioService->logCall($call->sid, $this->contactId, 'outbound', null, 'initiated');
            $this->dispatch('callInitiated', sid: $call->sid);
        } else {
            $this->addError('call', 'Failed to initiate call');
        }
    }

    public function startRecording(): void
    {
        if (! $this->callSid) {
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

    public function stopRecording(): void
    {
        if (! $this->callSid || $this->recordingStatus !== 'recording') {
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

    public function endCall(): void
    {
        if (! $this->callSid) {
            $this->addError('call', 'No active call to end');
            return;
        }

        $twilioService = app(TwilioService::class);
        $success = $twilioService->endCall($this->callSid);

        if ($success) {
            $this->status = 'ended';
            $this->callSid = null;
        } else {
            $this->addError('call', 'Failed to end call');
        }
    }

    public function saveNotes(): void
    {
        CallLog::where('call_sid', $this->callSid)->update(['notes' => $this->notes]);
        $this->dispatch('notesSaved');
    }

    #[On('updateCallStatus')]
    public function updateCallStatus(string $status): void
    {
        $this->status = $status;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.call-manager');
    }
}
