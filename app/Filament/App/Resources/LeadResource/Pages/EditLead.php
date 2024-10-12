<?php

namespace App\Filament\App\Resources\LeadsResource\Pages;

use App\Filament\App\Resources\LeadsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeads extends EditRecord
{
    protected static string $resource = LeadsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
