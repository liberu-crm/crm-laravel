<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\DocumentResource\Pages;

use App\Filament\Portal\Resources\DocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
