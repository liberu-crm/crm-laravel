<?php

namespace App\Events;

use App\Models\Deal;
use App\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;

class DealClosed
{
    use Dispatchable;

    public function __construct(public Deal $deal)
    {
    }

    /** Team this event belongs to — used to scope notifications (anti cross-tenant leak). */
    public function team(): ?Team
    {
        return $this->deal->team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->deal->id,
            'name' => $this->deal->name,
            'value' => $this->deal->value,
            'stage' => $this->deal->stage,
            'close_date' => $this->deal->close_date?->toDateString(),
            'team_id' => $this->deal->team_id,
        ];
    }
}
