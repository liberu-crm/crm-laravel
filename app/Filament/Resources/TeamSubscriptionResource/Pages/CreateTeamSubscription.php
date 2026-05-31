<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamSubscriptionResource\Pages;

use App\Filament\Resources\TeamSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamSubscription extends CreateRecord
{
    protected static string $resource = TeamSubscriptionResource::class;
}
