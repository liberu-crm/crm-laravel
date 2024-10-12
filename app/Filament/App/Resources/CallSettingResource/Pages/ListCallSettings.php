<?php

namespace App\Filament\App\Resources\CallSettingResource\Pages;

use App\Filament\App\Resources\CallSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallSettings extends ListRecords
{
    protected static string $resource = CallSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
