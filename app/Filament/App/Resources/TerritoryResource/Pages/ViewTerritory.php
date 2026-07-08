<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TerritoryResource\Pages;

use App\Filament\App\Resources\TerritoryResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewTerritory extends ViewRecord
{
    protected static string $resource = TerritoryResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('users.name')
                ->label('Members')
                ->listWithLineBreaks()
                ->badge(),
        ]);
    }
}
