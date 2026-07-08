<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\TeamMemberResource\Pages\ListTeamMembers;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Team-admin self-service role management (F4 phase 2). Team-scoped: a team admin
 * sees and re-roles only their own team's members, and can only assign the four
 * team roles — super_admin (global) and customer (portal) are never offered.
 */
class TeamMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Team roles';

    protected static ?string $slug = 'team-roles';

    // Model is User, which is a member of many teams — it has no `team`
    // ownership relationship, so it can't use the app panel's automatic
    // tenant scoping. Opt out and scope to the tenant's members manually in
    // getEloquentQuery (else Filament throws resolving the `team` relationship).
    protected static bool $isScopedToTenant = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole([Role::Admin, Role::SuperAdmin]);
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        $ids = $tenant instanceof Team ? $tenant->allUsers()->pluck('id')->all() : [];

        return User::query()->whereKey($ids);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('team_role')
                    ->label('Role')
                    ->badge()
                    // The request's permission team is the tenant, so getRoleNames()
                    // returns this member's team-scoped role.
                    ->getStateUsing(fn (User $record): string => $record->getRoleNames()->first() ?? '—'),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Change role')
                    ->icon('heroicon-o-key')
                    // Hidden on the acting admin's own row (no self-lockout) and on
                    // the team owner's row (their role is immutable — changeTeamRole
                    // rejects it too).
                    ->visible(function (User $record): bool {
                        $tenant = Filament::getTenant();
                        $ownerId = $tenant instanceof Team ? $tenant->getAttribute('user_id') : null;

                        return $record->getKey() !== Auth::id() && $record->getKey() !== $ownerId;
                    })
                    ->schema([
                        Select::make('role')
                            ->options([
                                Role::Admin->value => 'Admin',
                                Role::Manager->value => 'Manager',
                                Role::SalesRep->value => 'Sales rep',
                                Role::Free->value => 'Free',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data, TeamManagementService $service): void {
                        $tenant = Filament::getTenant();
                        if ($tenant instanceof Team) {
                            $service->changeTeamRole($record, $tenant, Role::from($data['role']));
                            Notification::make()->title('Role updated')->success()->send();
                        }
                    }),
            ])
            ->defaultSort('name');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTeamMembers::route('/'),
        ];
    }
}
