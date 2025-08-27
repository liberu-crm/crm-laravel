<?php

namespace App\Filament\App\Resources\LeadResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
