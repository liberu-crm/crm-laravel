<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MarketingCampaignResource\Pages;

use App\Filament\App\Resources\MarketingCampaignResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMarketingCampaign extends ViewRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('type'),
            TextEntry::make('status'),
            TextEntry::make('subject'),
            TextEntry::make('scheduled_at')->dateTime(),
            TextEntry::make('content')->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
