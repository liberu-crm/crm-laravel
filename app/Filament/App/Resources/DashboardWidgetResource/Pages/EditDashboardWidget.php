<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DashboardWidgetResource\Pages;

use App\Filament\App\Resources\DashboardWidgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDashboardWidget extends EditRecord
{
    protected static string $resource = DashboardWidgetResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
