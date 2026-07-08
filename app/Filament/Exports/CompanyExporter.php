<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Company;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for team companies. Used by the app-panel CompanyResource
 * header ExportAction, which is hidden for masked (`free`) roles — so
 * phone_number and annual_revenue are exported in the clear here on purpose:
 * only non-masking roles can ever trigger it. The export inherits the
 * resource's tenant scope (Company is IsTenantModel), so it only emits the
 * current team's rows.
 *
 * ponytail: freeform `description`/`location` and internal ids are omitted —
 * they aren't meaningful flat CSV columns. Add a column here if a consumer
 * ever needs one.
 */
class CompanyExporter extends Exporter
{
    protected static ?string $model = Company::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('industry'),
            ExportColumn::make('website'),
            ExportColumn::make('phone_number'),
            ExportColumn::make('annual_revenue'),
            ExportColumn::make('address'),
            ExportColumn::make('city'),
            ExportColumn::make('state'),
            ExportColumn::make('zip'),
            ExportColumn::make('size'),
            ExportColumn::make('domain'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your company export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
