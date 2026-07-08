<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamRoleLogResource\Pages;

use App\Filament\App\Resources\TeamRoleLogResource;
use Filament\Resources\Pages\ListRecords;

class ListTeamRoleLogs extends ListRecords
{
    protected static string $resource = TeamRoleLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
