<?php

namespace App\Filament\App\Resources\LandingPagesResource\Pages;

use App\Filament\App\Resources\LandingPagesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingPagess extends ListRecords
{
    protected static string $resource = LandingPagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
