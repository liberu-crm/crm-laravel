<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TerritoryResource\Pages;

use App\Filament\App\Resources\TerritoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTerritory extends EditRecord
{
    protected static string $resource = TerritoryResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
