<?php

namespace App\Filament\App\Resources\AdSetResource\Pages;

use App\Filament\App\Resources\AdSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdSet extends EditRecord
{
    protected static string $resource = AdSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
