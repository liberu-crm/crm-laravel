<?php

namespace App\Filament\App\Pages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Config;

class TwilioSettings extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog';

    protected string $view = 'filament.app.pages.twilio-settings';

    public ?string $sid = null;
    public ?string $auth_token = null;
    public ?string $phone_number = null;

    public function mount(): void
    {
        $this->sid = config('services.twilio.sid');
        $this->auth_token = config('services.twilio.auth_token');
        $this->phone_number = config('services.twilio.phone_number');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sid')
                    ->label('Twilio SID')
                    ->required(),
                TextInput::make('auth_token')
                    ->label('Twilio Auth Token')
                    ->password()
                    ->required(),
                TextInput::make('phone_number')
                    ->label('Twilio Phone Number')
                    ->tel()
                    ->required(),
            ]);
    }

    public function submit(): void
    {
        $this->validate();

        // Update the configuration
        Config::set('services.twilio.sid', $this->sid);
        Config::set('services.twilio.auth_token', $this->auth_token);
        Config::set('services.twilio.phone_number', $this->phone_number);

        // You might want to save these settings to the database or .env file for persistence

        Notification::make()
            ->title('Twilio settings updated successfully')
            ->success()
            ->send();
    }
}