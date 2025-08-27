<?php

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use App\Filament\App\Resources\MailchimpCampaignResource;
use Filament\Resources\Pages\Page;
use Filament\Pages\Concerns\InteractsWithFormActions;

class ViewMailchimpCampaign extends Page
{
    use InteractsWithFormActions;

    protected static string $resource = MailchimpCampaignResource::class;

    protected string $view = 'filament.app.resources.mailchimp.view';

    public function getTitle(): string
    {
        return 'MailchimpCampaign ';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MailchimpCampaignResource::getWidgets()[0],
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            MailchimpCampaignResource::getWidgets()[1],
            MailchimpCampaignResource::getWidgets()[2],
        ];
    }
}
