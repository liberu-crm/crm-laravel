<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\WhatsAppNumberResource\Pages;

use App\Filament\App\Resources\WhatsAppNumberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppNumbers extends ListRecords
{
    protected static string $resource = WhatsAppNumberResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
