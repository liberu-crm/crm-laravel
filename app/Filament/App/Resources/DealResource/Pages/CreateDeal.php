<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DealResource\Pages;

use App\Filament\App\Resources\DealResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;
}
