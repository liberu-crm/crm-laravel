<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\TeamBackupResource\Pages\ListTeamBackups;
use App\Models\TeamBackup;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Super-admin-only view of team data-export backups: trigger new ones, download
 * completed archives, delete old ones. Backups contain PII, so downloads stream
 * through this gated action off a private disk — never a public URL.
 */
class TeamBackupResource extends Resource
{
    protected static ?string $model = TeamBackup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole(Role::SuperAdmin);
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('team.name')->label('Team')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (TeamBackup $record): bool => $record->status === 'completed' && $record->path !== null)
                    ->action(fn (TeamBackup $record) => Storage::disk($record->disk)->download((string) $record->path)),
                DeleteAction::make()
                    ->before(function (TeamBackup $record): void {
                        if ($record->path !== null) {
                            Storage::disk($record->disk)->delete($record->path);
                        }
                    }),
            ]);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTeamBackups::route('/'),
        ];
    }
}
