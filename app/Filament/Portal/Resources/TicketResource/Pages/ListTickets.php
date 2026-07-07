<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Filament\Portal\Resources\TicketResource;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    // No create action — customers can view and reply, not raise tickets here
    // (portal ticket creation is a later G_5 slice).
    protected function getHeaderActions(): array
    {
        return [];
    }
}
