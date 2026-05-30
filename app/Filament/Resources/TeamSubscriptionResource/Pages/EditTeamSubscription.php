<?php

namespace App\Filament\Resources\TeamSubscriptionResource\Pages;

use App\Filament\Resources\TeamSubscriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamSubscription extends EditRecord
{
    protected static string $resource = TeamSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
