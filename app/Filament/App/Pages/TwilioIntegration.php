<?php

namespace App\Filament\App\Pages;

use Filament\Schemas\Schema;
use App\Services\TwilioService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Twilio\Exceptions\RestException;

class TwilioIntegration extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-phone';

    protected string $view = 'filament.app.pages.twilio-integration';

    public ?string $to = null;
    public ?string $message = null;

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
}
