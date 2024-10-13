<?php

namespace App\Filament\App\Pages;

use App\Services\TwilioService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TwilioIntegration extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static string $view = 'filament.app.pages.twilio-integration';

    public ?string $to = null;
    public ?string $message = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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

        $twilioService = app(TwilioService::class);
        $result = $twilioService->sendSMS($this->to, $this->message);

        if ($result) {
            Notification::make()
                ->title('SMS sent successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to send SMS')
                ->danger()
                ->send();
        }
    }

    public function makeCall()
    {
        $this->validate([
            'to' => 'required',
        ]);

        $twilioService = app(TwilioService::class);
        $result = $twilioService->makeCall($this->to, route('twilio.twiml.outbound'));

        if ($result) {
            Notification::make()
                ->title('Call initiated successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to initiate call')
                ->danger()
                ->send();
        }
    }
}