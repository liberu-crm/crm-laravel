<?php

namespace App\Filament\App\Pages;

use App\Services\AuditLogService;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformationPage extends Page
{
    protected string $view = 'filament.app.pages.update-profile-information-page';

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
