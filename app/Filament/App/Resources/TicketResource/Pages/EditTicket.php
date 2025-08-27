<?php

namespace App\Filament\App\Resources\TicketResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
