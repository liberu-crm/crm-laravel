<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;

// ponytail: FLAG — ambiguous trigger, no dispatch wired. There is no Meeting model.
// Activity is a generic timeline log (type is created/updated/deleted/commented, no
// "meeting"), so auto-dispatching on Activity created would over-notify on every
// logged action. Wire this only once a real meeting model/type exists (e.g. dispatch
// when an Activity is created with type === 'meeting'), which is a product decision.
class MeetingScheduled
{
    use Dispatchable;

    public function __construct(public Activity $activity)
    {
    }

    /** Team this event belongs to — used to scope notifications (anti cross-tenant leak). */
    public function team(): ?Team
    {
        return $this->activity->team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->activity->id,
            'type' => $this->activity->type,
            'date' => $this->activity->date?->toDateTimeString(),
            'description' => $this->activity->description,
            'team_id' => $this->activity->getAttribute('team_id'),
        ];
    }
}
