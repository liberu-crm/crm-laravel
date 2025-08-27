<?php

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\OpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return OpportunityResource::getPipelineTable($table);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OpportunityResource::getPipelineView(),
        ];
    }
}
