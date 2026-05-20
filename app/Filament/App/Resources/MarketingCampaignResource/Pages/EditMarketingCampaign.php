<?php

namespace App\Filament\App\Resources\MarketingCampaignResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\MarketingCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
