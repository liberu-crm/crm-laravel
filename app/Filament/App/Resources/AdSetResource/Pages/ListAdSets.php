<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AdSetResource\Pages;

use App\Filament\App\Resources\AdSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdSets extends ListRecords
{
    protected static string $resource = AdSetResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
