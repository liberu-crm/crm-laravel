<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Filament\App\Resources\AuditLogResource\Pages\ViewAuditLog;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Filament\Exports\AuditLogExporter;
use App\Models\AuditLog;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Team-scoped, read-only view of the team's full audit trail. AuditLog is
 * IsTenantModel, so the tenant global scope limits rows to the current team —
 * this is the app-panel counterpart to the admin panel's global AuditLogResource,
 * and a superset of TeamRoleLogResource (team.*) / PortalAccessLogResource
 * (portal.*): it also surfaces record CRUD and auth events.
 */
class AuditLogResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $slug = 'audit-log';

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('By')->searchable(),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                TextColumn::make('description')->wrap()->searchable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'record' => 'Record changes',
                        'team' => 'Team',
                        'portal' => 'Portal',
                        'auth' => 'Auth',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'record' => $query->whereIn('action', ['created', 'updated', 'deleted']),
                        'team' => $query->where('action', 'like', 'team.%'),
                        'portal' => $query->where('action', 'like', 'portal.%'),
                        'auth' => $query->where(fn (Builder $q): Builder => $q
                            ->where('action', 'like', 'auth.%')
                            ->orWhere('action', 'like', 'login%')),
                        default => $query,
                    }),
            ])
            ->headerActions([
                ExportAction::make()->exporter(AuditLogExporter::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
