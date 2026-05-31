<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\FormBuilderResource\Pages;

use App\Filament\App\Resources\FormBuilderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFormBuilder extends CreateRecord
{
    protected static string $resource = FormBuilderResource::class;
}
