<?php

namespace App\Filament\App\Resources\WhatsAppNumberResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\WhatsAppNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppNumber extends EditRecord
{
    protected static string $resource = WhatsAppNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
