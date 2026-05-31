<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\WhatsAppNumberResource\Pages;

use App\Filament\App\Resources\WhatsAppNumberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppNumber extends EditRecord
{
    protected static string $resource = WhatsAppNumberResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
