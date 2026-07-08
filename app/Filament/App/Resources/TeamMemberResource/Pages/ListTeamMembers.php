<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamMemberResource\Pages;

use App\Filament\App\Resources\TeamMemberResource;
use Filament\Resources\Pages\ListRecords;

class ListTeamMembers extends ListRecords
{
    protected static string $resource = TeamMemberResource::class;
}
