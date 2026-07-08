<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Contact;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for team contacts. Used by the app-panel ContactResource
 * header ExportAction, which is hidden for masked (`free`) roles — so email and
 * phone_number are exported in the clear here on purpose: only non-masking roles
 * can ever trigger it. The export inherits the resource's tenant scope (Contact
 * is IsTenantModel), so it only emits the current team's rows.
 *
 * ponytail: `custom_fields` / `metadata` (array-cast columns) are omitted — a
 * nested array does not flatten into a single CSV cell cleanly. Add a
 * JSON-encoded column here if a flat serialisation is ever required.
 */
class ContactExporter extends Exporter
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('last_name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone_number'),
            ExportColumn::make('status'),
            ExportColumn::make('source'),
            ExportColumn::make('industry'),
            ExportColumn::make('company_size'),
            ExportColumn::make('annual_revenue'),
            ExportColumn::make('lifecycle_stage'),
            ExportColumn::make('company.name')->label('Company'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your contact export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
