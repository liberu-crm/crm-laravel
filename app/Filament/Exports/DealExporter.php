<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Deal;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV/XLSX exporter for team deals. Used by the app-panel DealResource header
 * ExportAction, which is hidden for masked (`free`) roles — so `value` is
 * exported in the clear here on purpose: only non-masking roles can ever trigger
 * it. The export inherits the resource's tenant scope (Deal is IsTenantModel),
 * so it only emits the current team's rows.
 *
 * ponytail: internal ids (team_id/user_id/contact_id/pipeline_id/stage_id) are
 * omitted in favour of the human-readable relation labels below. `stage_id` is
 * skipped entirely — `stage` is also a plain string column, so `stage.name`
 * would resolve the string attribute, not the Stage relation.
 */
class DealExporter extends Exporter
{
    protected static ?string $model = Deal::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('value'),
            ExportColumn::make('stage'),
            ExportColumn::make('close_date'),
            ExportColumn::make('probability'),
            ExportColumn::make('contact.name')->label('Contact'),
            ExportColumn::make('user.name')->label('Owner'),
            ExportColumn::make('pipeline.name')->label('Pipeline'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your deal export has completed and '
            .number_format($export->successful_rows).' '
            .str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '
                .str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
