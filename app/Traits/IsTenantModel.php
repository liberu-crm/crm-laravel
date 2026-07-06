<?php

namespace App\Traits;

use App\Models\Team;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait IsTenantModel
{
    /**
     * Auto-boots for every model using this trait: a removable global scope
     * that filters reads to the current team, plus auto-stamping team_id on
     * create. Both are inert when TenantContext::currentId() is null
     * (admin panel, console, queue, un-scoped tests).
     */
    protected static function bootIsTenantModel(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if ($teamId = TenantContext::currentId()) {
                $model = $builder->getModel();
                $builder->where($model->qualifyColumn('team_id'), $teamId);
            }
        });

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('team_id')) && $teamId = TenantContext::currentId()) {
                $model->setAttribute('team_id', $teamId);
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeByTeam(Builder $query, ?int $teamId): void
    {
        if ($teamId) {
            $query->where($query->qualifyColumn('team_id'), (int) $teamId);
        }
    }

    public function belongsToTeam(?int $teamId): bool
    {
        return ! $teamId || $this->team_id === $teamId;
    }
}
