<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SamlConnectionResource\Pages;

use App\Filament\App\Resources\SamlConnectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSamlConnection extends CreateRecord
{
    protected static string $resource = SamlConnectionResource::class;
}
