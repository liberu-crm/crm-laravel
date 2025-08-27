<?php

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
