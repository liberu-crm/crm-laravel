<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Opportunity;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for team opportunities. Used by the app-panel
 * OpportunityResource header ExportAction, which is hidden for masked (`free`)
 * roles — so `deal_size` (the deal value) is exported in the clear here on
 * purpose: only non-masking roles can ever trigger it. The export inherits the
 * resource's tenant scope (Opportunity is IsTenantModel), so it only emits the
 * current team's rows.
 */
class OpportunityExporter extends Exporter
{
    protected static ?string $model = Opportunity::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('deal_size'),
            ExportColumn::make('stage'),
            ExportColumn::make('closing_date'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your opportunity export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
