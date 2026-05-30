<?php

namespace App\Filament\Resources\TeamSubscriptionResource\Pages;

use App\Filament\Resources\TeamSubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamSubscriptions extends ListRecords
{
    protected static string $resource = TeamSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
