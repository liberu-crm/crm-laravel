<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MarketingCampaignResource\Pages;

use App\Filament\App\Resources\MarketingCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketingCampaigns extends ListRecords
{
    protected static string $resource = MarketingCampaignResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
