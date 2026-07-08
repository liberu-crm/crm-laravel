<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SsoConnectionResource\Pages;

use App\Filament\App\Resources\SsoConnectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSsoConnection extends CreateRecord
{
    protected static string $resource = SsoConnectionResource::class;
}
