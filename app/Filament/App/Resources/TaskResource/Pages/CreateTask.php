<?php

namespace App\Filament\App\Resources\TaskResource\Pages;

use App\Filament\App\Resources\TaskResource;
use App\Services\GoogleCalendarService;
use App\Services\OutlookCalendarService;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function getFormSchema(): array
    {
        return array_merge(
            parent::getFormSchema(),
            [
                DateTimePicker::make('reminder_date')
                    ->label('Reminder Date'),
                Select::make('calendar_type')
                    ->label('Sync to Calendar')
                    ->options([
                        'none' => 'No Sync',
                        'google' => 'Google Calendar',
                        'outlook' => 'Outlook Calendar',
                    ])
                    ->default('none')
                    ->reactive(),
            ]
        );
    }

    protected function afterCreate(): void
    {
        if ($this->record->calendar_type !== 'none') {
            $this->record->syncWithCalendar();
        }
    }
}
