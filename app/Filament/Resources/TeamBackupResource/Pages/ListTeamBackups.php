<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamBackupResource\Pages;

use App\Filament\Resources\TeamBackupResource;
use App\Jobs\GenerateTeamBackup;
use App\Models\Team;
use App\Models\TeamBackup;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTeamBackups extends ListRecords
{
    protected static string $resource = TeamBackupResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate backup')
                ->icon('heroicon-o-plus')
                ->schema([
                    Select::make('team_id')
                        ->label('Team')
                        ->options(fn (): array => Team::withoutGlobalScope('archived')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $backup = TeamBackup::create([
                        'team_id' => (int) $data['team_id'],
                        'disk' => (string) config('filesystems.default', 'local'),
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                    ]);

                    GenerateTeamBackup::dispatch($backup->id);

                    Notification::make()->title('Backup queued')->success()->send();
                }),
        ];
    }
}
