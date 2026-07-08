<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamRoleResource\Pages;

use App\Filament\App\Resources\TeamRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamRoles extends ListRecords
{
    protected static string $resource = TeamRoleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
