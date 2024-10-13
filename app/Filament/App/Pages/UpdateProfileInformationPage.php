<?php

namespace App\Filament\App\Pages;

use App\Services\AuditLogService;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    protected static string $view = 'filament.app.pages.update-profile-information-page';

    public $name;
    public $email;

    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        parent::__construct();
        $this->auditLogService = $auditLogService;
    }

    public function mount()
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
        ];
    }

    public function submit()
    {
        $this->validate();

        $user = Auth::user();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();

        $this->auditLogService->log('profile_update', 'User updated profile information');

        $this->notify('success', 'Your profile information has been updated.');
    }
}

namespace App\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    protected static string $view = 'filament.pages.profile.update-profile-information';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Profile';

    public $name;

    public $email;

    public function mount(): void
    {
        $this->form->fill([
            'name'  => Auth::user()->name,
            'email' => Auth::user()->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
            TextInput::make('email')
                ->label('Email Address')
                ->required(),
        ];
    }

    public function submit(): void
    {
        $this->form->getState();

        $state = array_filter([
            'name'  => $this->name,
            'email' => $this->email,
        ]);

        $user = Auth::user();

        $user->forceFill($state)->save();

        session()->flash('status', 'Your profile has been updated.');
    }

    public function getHeading(): string
    {
        return static::$title;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true; //config('filament-jetstream.show_update_profile_information_page');
    }
}
