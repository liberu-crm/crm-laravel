<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaskResource\Pages;

use App\Filament\App\Resources\TaskResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('description'),
            TextEntry::make('status'),
            TextEntry::make('due_date')->dateTime(),
            TextEntry::make('contact.name')->label('Contact'),
            TextEntry::make('reminder_date')->dateTime(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
