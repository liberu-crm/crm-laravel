<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\TeamRoleLogResource\Pages\ListTeamRoleLogs;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Team-scoped, read-only view of team role changes (the `team.*` audit entries
 * from changeTeamRole). AuditLog is IsTenantModel, so on the team-scoped app
 * panel this shows only the current team's history — letting a team's own
 * admins see who re-roled whom (the admin panel's AuditLogResource is global).
 */
class TeamRoleLogResource extends Resource
{
    use EnforcesResourcePermissions;

    public static function permissionResource(): string
    {
        return 'team_role_log';
    }

    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Role change log';

    protected static ?string $slug = 'team-role-log';

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('action', 'like', 'team.%');
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('By')->searchable(),
                TextColumn::make('action')->badge(),
                TextColumn::make('description')->wrap()->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTeamRoleLogs::route('/'),
        ];
    }
}
