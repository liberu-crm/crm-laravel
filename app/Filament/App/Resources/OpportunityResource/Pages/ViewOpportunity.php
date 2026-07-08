<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\OpportunityResource\Pages;

use App\Filament\App\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Support\AccessContext;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('stage'),
            // Masked for masked-role (`free`) viewers, same gate as the table
            // column, so the detail view is not a masking bypass.
            TextEntry::make('deal_size')
                ->getStateUsing(fn (Opportunity $record): string => AccessContext::shouldMaskFields()
                    ? '[hidden]'
                    : '$'.number_format((float) $record->deal_size, 2)),
            TextEntry::make('closing_date')->date(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
