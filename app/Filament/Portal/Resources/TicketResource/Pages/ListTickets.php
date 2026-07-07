<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Filament\Portal\Resources\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    // CreateAction hides itself when TicketResource::canCreate() is false
    // (customer without a tenant), so team-less customers get no button.
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
