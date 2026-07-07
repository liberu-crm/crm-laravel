<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PortalAccessLogResource\Pages;

use App\Filament\App\Resources\PortalAccessLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPortalAccessLogs extends ListRecords
{
    protected static string $resource = PortalAccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
