<?php

namespace App\Filament\App\Resources\AdvertisingAccountResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\AdvertisingAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdvertisingAccount extends EditRecord
{
    protected static string $resource = AdvertisingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
