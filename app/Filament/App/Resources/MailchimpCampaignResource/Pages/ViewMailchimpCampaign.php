<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use App\Filament\App\Resources\MailchimpCampaignResource;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Page;

class ViewMailchimpCampaign extends Page
{
    use InteractsWithFormActions;

    protected static string $resource = MailchimpCampaignResource::class;

    protected string $view = 'filament.app.resources.mailchimp.view';

    #[\Override]
    public function getTitle(): string
    {
        return 'MailchimpCampaign ';
    }

    #[\Override]
    protected function getHeaderWidgets(): array
    {
        return [
            MailchimpCampaignResource::getWidgets()[0],
        ];
    }

    #[\Override]
    protected function getFooterWidgets(): array
    {
        return [
            MailchimpCampaignResource::getWidgets()[1],
            MailchimpCampaignResource::getWidgets()[2],
        ];
    }
}
