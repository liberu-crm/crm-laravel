<?php

namespace App\Filament\App\Pages;

use App\Services\AuditLogService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    protected string $view = 'filament.app.pages.update-profile-information-page';

    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
            ]);
    }

    public function submit(): void
    {
        $this->validate();

        $user = Auth::user();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();

        app(AuditLogService::class)->log('profile_update', 'User updated profile information');

        Notification::make()
            ->title('Your profile information has been updated.')
            ->success()
            ->send();
    }
}
