<?php

namespace App\Filament\App\Resources\WorkflowResource\Pages;

use App\Filament\App\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
