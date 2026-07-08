<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for team leads. Used by the app-panel LeadResource header
 * ExportAction, which is hidden for masked (`free`) roles — so potential_value
 * is exported in the clear here on purpose: only non-masking roles can ever
 * trigger it. The export inherits the resource's owner + tenant scope (Lead is
 * RestrictsToOwner + IsTenantModel), so it only emits rows the viewer may see.
 *
 * ponytail: `custom_fields` (array-cast column) and internal ids are omitted — a
 * nested array does not flatten into a single CSV cell cleanly. Add a
 * JSON-encoded column here if a flat serialisation is ever required.
 */
class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('status'),
            ExportColumn::make('source'),
            ExportColumn::make('potential_value'),
            ExportColumn::make('lifecycle_stage')->label('Lifecycle Stage'),
            ExportColumn::make('score')->label('Lead Score'),
            ExportColumn::make('expected_close_date'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your lead export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
