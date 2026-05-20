<?php

namespace App\Filament\App\Resources\LeadResource\Pages;

use App\Filament\App\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
}
