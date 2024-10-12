<?php

namespace App\Filament\App\Resources\LeadsResource\Pages;

use App\Filament\App\Resources\LeadsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeadss extends ListRecords
{
    protected static string $resource = LeadsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
