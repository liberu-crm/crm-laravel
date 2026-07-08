<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TerritoryResource\Pages;

use App\Filament\App\Resources\TerritoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTerritories extends ListRecords
{
    protected static string $resource = TerritoryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
