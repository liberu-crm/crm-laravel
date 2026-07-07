<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\PortalAccessLogResource\Pages\ListPortalAccessLogs;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Team-scoped, read-only view of portal access grants/revocations (the
 * `portal.*` audit entries from #490). AuditLog is IsTenantModel, so on the
 * team-scoped app panel this shows only the current team's history — letting a
 * team's own managers see who invited or revoked their customers (the admin
 * panel's AuditLogResource is super_admin/global).
 */
class PortalAccessLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Help Desk';

    protected static ?string $navigationLabel = 'Portal access log';

    protected static ?string $slug = 'portal-access-log';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole([Role::SuperAdmin, Role::Admin, Role::Manager]);
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('action', 'like', 'portal.%');
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
            'index' => ListPortalAccessLogs::route('/'),
        ];
    }
}
