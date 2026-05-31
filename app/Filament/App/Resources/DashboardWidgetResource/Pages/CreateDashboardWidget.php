<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DashboardWidgetResource\Pages;

use App\Filament\App\Resources\DashboardWidgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDashboardWidget extends CreateRecord
{
    protected static string $resource = DashboardWidgetResource::class;
}
