<?php

namespace App\Filament\App\Resources\AdResource\Pages;

use App\Filament\App\Resources\AdResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAd extends EditRecord
{
    protected static string $resource = AdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
