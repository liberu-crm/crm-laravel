<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\AccessContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Record-level territory scope (G3 ABAC). Stacks on IsTenantModel's team scope:
 * when AccessContext restricts the current user to a set of territories, reads
 * are filtered to rows whose territory_id is in that set. Read-only — territory
 * is assigned, not creator-derived, so there is no insert auto-stamp.
 */
trait RestrictsToTerritory
{
    protected static function bootRestrictsToTerritory(): void
    {
        static::addGlobalScope('territory', function (Builder $builder): void {
            $ids = AccessContext::restrictedTerritoryIds();

            if ($ids !== null) {
                // Empty set (a restricted user with no territories) -> no rows.
                $builder->whereIn($builder->getModel()->qualifyColumn('territory_id'), $ids);
            }
        });
    }
}
