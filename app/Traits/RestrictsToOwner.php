<?php

namespace App\Traits;

use App\Contracts\OwnsRecords;
use App\Support\AccessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Record-level ownership scope. Stacks on top of IsTenantModel's team scope:
 * when AccessContext restricts to an owner, reads are filtered to rows the
 * user owns, and the owner column is auto-stamped with the creator on insert.
 *
 * Owner column defaults to `user_id`; override with `protected $ownerColumn`.
 */
trait RestrictsToOwner
{
    public function ownerColumn(): string
    {
        return property_exists($this, 'ownerColumn') ? $this->ownerColumn : 'user_id';
    }

    protected static function bootRestrictsToOwner(): void
    {
        static::addGlobalScope('owner', function (Builder $builder): void {
            $ownerId = AccessContext::restrictToOwnerId();
            $model = $builder->getModel();

            if ($ownerId !== null && $model instanceof OwnsRecords) {
                $builder->where($model->qualifyColumn($model->ownerColumn()), $ownerId);
            }
        });

        static::creating(function (Model $model): void {
            if (! $model instanceof OwnsRecords) {
                return;
            }

            $column = $model->ownerColumn();

            if (empty($model->getAttribute($column))) {
                $creator = Auth::guard('sanctum')->user() ?? Auth::user();

                if ($creator !== null) {
                    $model->setAttribute($column, $creator->getAuthIdentifier());
                }
            }
        });
    }
}
