<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\NoteResource\Pages;

use App\Filament\App\Resources\NoteResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewNote extends ViewRecord
{
    protected static string $resource = NoteResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('content')->columnSpanFull(),
            TextEntry::make('contact.name')->label('Contact'),
            TextEntry::make('company.name')->label('Company'),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
