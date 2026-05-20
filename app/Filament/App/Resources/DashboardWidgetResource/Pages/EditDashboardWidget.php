<?php

namespace App\Filament\App\Resources\DashboardWidgetResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\DashboardWidgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDashboardWidget extends EditRecord
{
    protected static string $resource = DashboardWidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
