<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\AuditLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for the team audit log. Used by the app-panel
 * AuditLogResource header ExportAction. The export inherits the resource's
 * tenant scope (AuditLog is IsTenantModel) and admin gating, so it only ever
 * emits the current team's rows.
 *
 * ponytail: `changes` (array-cast column) is intentionally omitted — a nested
 * array does not flatten into a single CSV cell cleanly. Add a JSON-encoded
 * column here if a flat serialisation is ever required.
 */
class AuditLogExporter extends Exporter
{
    protected static ?string $model = AuditLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('created_at'),
            ExportColumn::make('user.name')->label('By'),
            ExportColumn::make('action'),
            ExportColumn::make('auditable_type')->label('Subject'),
            ExportColumn::make('auditable_id'),
            ExportColumn::make('ip_address'),
            ExportColumn::make('description'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your audit log export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
