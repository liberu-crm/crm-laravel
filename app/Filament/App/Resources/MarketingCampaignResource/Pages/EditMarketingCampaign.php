<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MarketingCampaignResource\Pages;

use App\Filament\App\Resources\MarketingCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
