<?php

namespace App\Filament\App\Resources\WorkflowResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
