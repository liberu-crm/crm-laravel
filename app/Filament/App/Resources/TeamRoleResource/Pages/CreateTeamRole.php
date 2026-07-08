<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamRoleResource\Pages;

use App\Filament\App\Resources\TeamRoleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

class CreateTeamRole extends CreateRecord
{
    protected static string $resource = TeamRoleResource::class;

    #[\Override]
    protected function handleRecordCreation(array $data): Model
    {
        // Clamp to the grantable set server-side so a crafted request can't grant
        // a management permission (anti-escalation).
        $grantable = array_keys(TeamRoleResource::grantablePermissions());
        $permissions = array_values(array_intersect($data['permissions'] ?? [], $grantable));
        unset($data['permissions']);

        $data['team_id'] = Filament::getTenant()?->getKey();
        $data['guard_name'] = 'web';

        $role = SpatieRole::create($data);
        $role->syncPermissions($permissions);

        return $role;
    }
}
