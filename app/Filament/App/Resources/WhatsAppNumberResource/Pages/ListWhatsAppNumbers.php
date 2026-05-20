<?php

namespace App\Filament\App\Resources\WhatsAppNumberResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\WhatsAppNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppNumbers extends ListRecords
{
    protected static string $resource = WhatsAppNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
