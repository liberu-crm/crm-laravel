<?php

namespace App\Filament\App\Resources\LandingPageResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\LandingPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingPages extends ListRecords
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
