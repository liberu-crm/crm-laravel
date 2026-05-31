<?php

namespace App\Filament\App\Resources\TaskResource\Pages;

use App\Filament\App\Resources\TaskResource;
use App\Services\GoogleCalendarService;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    #[\Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->components([
                ...parent::form($schema)->getComponents(),
                DateTimePicker::make('reminder_date')
                    ->label('Reminder Date'),
                Toggle::make('sync_to_google_calendar')
                    ->label('Sync to Google Calendar')
                    ->default(false),
            ]);
    }

    protected function afterSave(): void
    {
        if ($this->record->sync_to_google_calendar) {
            $googleCalendarService = app(GoogleCalendarService::class);
            $googleCalendarService->updateEvent($this->record);
        }
    }
}
