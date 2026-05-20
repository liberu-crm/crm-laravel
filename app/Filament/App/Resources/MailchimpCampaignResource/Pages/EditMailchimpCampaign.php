<?php

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\MailchimpCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailchimpCampaign extends EditRecord
{
    protected static string $resource = MailchimpCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
