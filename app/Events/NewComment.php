<?php

namespace App\Events;

use App\Models\Note;
use App\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;

// Note is this app's comment model (a `content` body attached to a contact/company/
// opportunity), so NewComment fires on Note created.
class NewComment
{
    use Dispatchable;

    public function __construct(public Note $note)
    {
    }

    /** Team this event belongs to — used to scope notifications (anti cross-tenant leak). */
    public function team(): ?Team
    {
        return $this->note->team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->note->id,
            'content' => $this->note->content,
            'contact_id' => $this->note->contact_id,
            'company_id' => $this->note->company_id,
            'opportunity_id' => $this->note->opportunity_id,
            'team_id' => $this->note->getAttribute('team_id'),
        ];
    }
}
