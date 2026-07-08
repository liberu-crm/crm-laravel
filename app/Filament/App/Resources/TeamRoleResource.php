<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\TeamRoleResource\Pages\CreateTeamRole;
use App\Filament\App\Resources\TeamRoleResource\Pages\EditTeamRole;
use App\Filament\App\Resources\TeamRoleResource\Pages\ListTeamRoles;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A team admin manages their team's CUSTOM roles (F4). These are spatie roles
 * scoped to the current team via roles.team_id; the four system roles (team_id
 * null) are managed globally and never shown/edited here. Permissions that
 * manage roles/permissions/users are NOT grantable — a team admin can't mint a
 * role that escalates privilege.
 */
class TeamRoleResource extends Resource
{
    protected static ?string $model = SpatieRole::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Custom roles';

    protected static ?string $slug = 'custom-roles';

    // Spatie Role has no `team` ownership relation, so opt out of Filament's
    // automatic tenant scoping and scope to roles.team_id manually below.
    protected static bool $isScopedToTenant = false;

    /**
     * Permissions a team admin may grant to a custom role: everything except
     * role/permission/user management (which would enable privilege escalation).
     *
     * @return array<string, string>
     */
    public static function grantablePermissions(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['manage_roles', 'manage_permissions', 'manage_users'])
            ->where('name', 'not like', '%_role%')
            ->where('name', 'not like', '%_permission%')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole([Role::SuperAdmin, Role::Admin]);
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        $teamId = Auth::user()?->currentTeam?->getKey();

        return SpatieRole::query()->where('team_id', $teamId);
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                // Custom roles can't shadow a system role name.
                ->rule(Rule::notIn(Role::values())),
            CheckboxList::make('permissions')
                ->options(self::grantablePermissions())
                ->columns(2)
                ->searchable()
                ->bulkToggleable()
                ->helperText('Role and permission management cannot be granted to a custom role.'),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('permissions_count')->counts('permissions')->label('Permissions'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTeamRoles::route('/'),
            'create' => CreateTeamRole::route('/create'),
            'edit' => EditTeamRole::route('/{record}/edit'),
        ];
    }
}
