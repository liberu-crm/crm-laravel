<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\WebhookDeliveryResource\Pages;

use App\Filament\App\Resources\WebhookDeliveryResource;
use Filament\Resources\Pages\ListRecords;

class ListWebhookDeliveries extends ListRecords
{
    protected static string $resource = WebhookDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
