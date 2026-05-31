<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ActivationResource\Pages;

use App\Filament\App\Resources\ActivationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivation extends CreateRecord
{
    protected static string $resource = ActivationResource::class;
}
