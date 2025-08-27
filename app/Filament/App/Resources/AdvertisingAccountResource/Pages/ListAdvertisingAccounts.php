<?php

namespace App\Filament\App\Resources\AdvertisingAccountResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\AdvertisingAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisingAccounts extends ListRecords
{
    protected static string $resource = AdvertisingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
