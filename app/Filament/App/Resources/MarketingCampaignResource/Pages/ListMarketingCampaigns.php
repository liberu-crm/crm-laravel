<?php

namespace App\Filament\App\Resources\MarketingCampaignResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\MarketingCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketingCampaigns extends ListRecords
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
