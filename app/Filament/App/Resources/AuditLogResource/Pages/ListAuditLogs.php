<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AuditLogResource\Pages;

use App\Filament\App\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;
}
