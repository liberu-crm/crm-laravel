<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\AdSet;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * Budget is exported in the clear on purpose: the ExportAction that drives this
 * exporter is hidden for masked (`free`) roles, so a masked user never reaches
 * the export. The export also inherits the resource's tenant scope, so rows are
 * limited to the current team.
 */
class AdSetExporter extends Exporter
{
    protected static ?string $model = AdSet::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('status'),
            ExportColumn::make('budget'),
            ExportColumn::make('budget_type'),
            ExportColumn::make('external_id'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your ad set export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
