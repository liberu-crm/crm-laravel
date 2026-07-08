<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Campaign;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for team campaigns. Used by the app-panel CampaignResource
 * header ExportAction, which is hidden for masked (`free`) roles — so `budget`
 * is exported in the clear here on purpose: only non-masking roles can ever
 * trigger it. The export inherits the resource's tenant scope (Campaign is
 * IsTenantModel), so it only emits the current team's rows.
 */
class CampaignExporter extends Exporter
{
    protected static ?string $model = Campaign::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('status'),
            ExportColumn::make('objective'),
            ExportColumn::make('budget'),
            ExportColumn::make('budget_type'),
            ExportColumn::make('start_date'),
            ExportColumn::make('end_date'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your campaign export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
