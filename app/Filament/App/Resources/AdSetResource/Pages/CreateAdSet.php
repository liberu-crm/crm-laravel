<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AdSetResource\Pages;

use App\Filament\App\Resources\AdSetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdSet extends CreateRecord
{
    protected static string $resource = AdSetResource::class;
}
