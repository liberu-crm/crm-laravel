<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait IsTenantModel
{
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
