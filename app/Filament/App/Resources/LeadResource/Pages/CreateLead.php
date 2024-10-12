<?php

namespace App\Filament\App\Resources\LeadsResource\Pages;

use App\Filament\App\Resources\LeadsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadsResource::class;
}
