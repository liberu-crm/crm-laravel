<?php

namespace App\Filament\App\Resources\TaskResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TaskResource;
use App\Services\GoogleCalendarService;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormSchema(): array
    {
        return array_merge(
            parent::getFormSchema(),
            [
                DateTimePicker::make('reminder_date')
                    ->label('Reminder Date'),
                Toggle::make('sync_to_google_calendar')
                    ->label('Sync to Google Calendar')
                    ->default(false),
            ]
        );
    }

    protected function afterSave(): void
    {
        if ($this->record->sync_to_google_calendar) {
            $googleCalendarService = app(GoogleCalendarService::class);
            $googleCalendarService->updateEvent($this->record);
        }
    }
}
