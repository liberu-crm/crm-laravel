<?php

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\MailchimpCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMailchimpCampaigns extends ListRecords
{
    protected static string $resource = MailchimpCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
