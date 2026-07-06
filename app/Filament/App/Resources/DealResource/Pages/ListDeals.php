<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DealResource\Pages;

use App\Filament\App\Resources\DealResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
