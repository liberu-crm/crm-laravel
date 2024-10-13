<?php

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use App\Filament\App\Resources\MailchimpCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailchimpCampaign extends EditRecord
{
    protected static string $resource = MailchimpCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
