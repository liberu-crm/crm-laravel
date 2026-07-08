<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TeamRoleResource\Pages;

use App\Filament\App\Resources\TeamRoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

class EditTeamRole extends EditRecord
{
    protected static string $resource = TeamRoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var SpatieRole $role */
        $role = $this->record;
        $data['permissions'] = $role->getPermissionNames()->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[\Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SpatieRole $record */
        $grantable = array_keys(TeamRoleResource::grantablePermissions());
        $permissions = array_values(array_intersect($data['permissions'] ?? [], $grantable));
        unset($data['permissions']);

        $record->update($data);
        $record->syncPermissions($permissions);

        return $record;
    }
}
