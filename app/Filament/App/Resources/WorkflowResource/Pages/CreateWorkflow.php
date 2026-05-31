<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\WorkflowResource\Pages;

use App\Filament\App\Resources\WorkflowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkflow extends CreateRecord
{
    protected static string $resource = WorkflowResource::class;
}
