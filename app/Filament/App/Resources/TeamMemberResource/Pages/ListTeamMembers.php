<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamMemberResource\Pages;

use App\Enums\Role;
use App\Filament\App\Resources\TeamMemberResource;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTeamMembers extends ListRecords
{
    protected static string $resource = TeamMemberResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('addMember')
                ->label('Add member')
                ->icon('heroicon-o-user-plus')
                ->schema([
                    TextInput::make('email')->email()->required(),
                    Select::make('role')
                        ->options([
                            Role::Admin->value => 'Admin',
                            Role::Manager->value => 'Manager',
                            Role::SalesRep->value => 'Sales rep',
                            Role::Free->value => 'Free',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, TeamManagementService $service): void {
                    $tenant = Filament::getTenant();
                    $user = User::where('email', $data['email'])->first();

                    // Existing users only — creating a new account is onboarding / SSO.
                    if (! $tenant instanceof Team || ! $user instanceof User) {
                        Notification::make()->title('No user found with that email')->danger()->send();

                        return;
                    }

                    $service->addTeamMember($user, $tenant, Role::from($data['role']));
                    Notification::make()->title('Member added')->success()->send();
                }),
        ];
    }
}
