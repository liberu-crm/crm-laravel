<?php

namespace App\Filament\App\Resources\TicketResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
