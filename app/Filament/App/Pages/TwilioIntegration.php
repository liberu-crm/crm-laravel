<?php

namespace App\Filament\App\Pages;

use Filament\Schemas\Schema;
use App\Models\Contact;
use App\Models\Lead;
use App\Services\TwilioService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Config;
use Twilio\Exceptions\RestException;

class TwilioIntegration extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-phone';

    protected string $view = 'filament.app.pages.twilio-integration';

    public ?string $to = null;
    public ?string $message = null;
    public ?string $sid = null;
    public ?string $auth_token = null;
    public ?string $phone_number = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('to')
                    ->label('To')
                    ->tel()
                    ->required(),
                Textarea::make('message')
                    ->label('Message')
                    ->required(),
            ]);
    }

    public function sendSMS()
    {
        $this->validate();

        try {
            $twilioService = app(TwilioService::class);
            $result = $twilioService->sendSMS($this->to, $this->message);

            if ($result) {
                Notification::make()
                    ->title('SMS sent successfully')
                    ->success()
                    ->send();
                $this->dispatch('notify', title: 'SMS sent successfully', color: 'success');
            } else {
                $this->addError('twilio', 'Failed to send SMS');
            }
        } catch (RestException $e) {
            $this->addError('twilio', $e->getMessage());
        }
    }

    public function bulkSendSMS(array $contactIds): void
    {
        $this->validate([
            'message' => 'required|string',
        ]);

        $twilioService = app(TwilioService::class);
        $failCount = 0;

        $contacts = Contact::whereIn('id', $contactIds)->get();

        foreach ($contacts as $contact) {
            if (!$contact->phone_number) {
                continue;
            }
            try {
                $twilioService->sendSMS($contact->phone_number, $this->message);
            } catch (RestException $e) {
                $failCount++;
            }
        }

        if ($failCount > 0) {
            $this->addError('twilio', 'Failed to send some or all bulk SMS messages');
        } else {
            Notification::make()
                ->title('Bulk SMS sent successfully')
                ->success()
                ->send();
            $this->dispatch('notify', title: 'Bulk SMS sent successfully', color: 'success');
        }
    }

    public function makeCall()
    {
        $this->validate([
            'to' => 'required',
        ]);

        try {
            $twilioService = app(TwilioService::class);
            $result = $twilioService->makeCall($this->to, route('twilio.twiml.outbound'));

            if ($result) {
                Notification::make()
                    ->title('Call initiated successfully')
                    ->success()
                    ->send();
                $this->dispatch('notify', title: 'Call initiated successfully', color: 'success');
            } else {
                $this->addError('twilio', 'Failed to initiate call');
            }
        } catch (RestException $e) {
            $this->addError('twilio', $e->getMessage());
        }
    }

    public function bulkMakeCall(array $leadIds): void
    {
        $twilioService = app(TwilioService::class);
        $failCount = 0;

        $leads = Lead::with('contact')->whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            $phone = optional($lead->contact)->phone_number;
            if (!$phone) {
                continue;
            }
            try {
                $twilioService->makeCall($phone, route('twilio.twiml.outbound'));
            } catch (RestException $e) {
                $failCount++;
            }
        }

        if ($failCount > 0) {
            $this->addError('twilio', 'Failed to initiate some or all bulk calls');
        } else {
            Notification::make()
                ->title('Bulk calls initiated successfully')
                ->success()
                ->send();
            $this->dispatch('notify', title: 'Bulk calls initiated successfully', color: 'success');
        }
    }

    public function submit(): void
    {
        $this->validate([
            'sid'          => 'required|string|min:1',
            'auth_token'   => 'required|string|min:1',
            'phone_number' => 'required|regex:/^\+[1-9]\d{1,14}$/',
        ]);

        Config::set('services.twilio.sid', $this->sid);
        Config::set('services.twilio.auth_token', $this->auth_token);
        Config::set('services.twilio.phone_number', $this->phone_number);

        Notification::make()
            ->title('Twilio settings updated')
            ->success()
            ->send();
        $this->dispatch('notify', title: 'Twilio settings updated', color: 'success');
    }
}

