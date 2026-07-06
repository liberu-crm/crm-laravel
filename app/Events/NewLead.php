<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;

class NewLead
{
    use Dispatchable;

    public function __construct(public Lead $lead)
    {
    }

    /** Team this event belongs to — used to scope notifications (anti cross-tenant leak). */
    public function team(): ?Team
    {
        return $this->lead->team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->lead->id,
            'status' => $this->lead->status,
            'source' => $this->lead->source,
            'potential_value' => $this->lead->potential_value,
            'lifecycle_stage' => $this->lead->lifecycle_stage,
            'team_id' => $this->lead->getAttribute('team_id'),
        ];
    }
}
