<?php

namespace App\Models;

use App\Contracts\OwnsRecords;
use App\Traits\IsTenantModel;
use App\Traits\RestrictsToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model implements OwnsRecords
{
    use HasFactory;
    use IsTenantModel;
    use RestrictsToOwner;

    /** Record-level ownership keys off the assignee, not a creator. */
    protected $ownerColumn = 'assigned_to';

    protected $primaryKey = 'id';

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'due_date',
        'status',
        'contact_id',
        'lead_id',
        'company_id',
        'opportunity_id',
        'reminder_date',
        'reminder_sent',
        'google_event_id',
        'outlook_event_id',
        'calendar_type',
        'assigned_to',
    ];

    protected $casts = [
        'reminder_date' => 'datetime',
        'reminder_sent' => 'boolean',
        'due_date' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function syncWithCalendar(): void
    {
        $calendarService = $this->getCalendarService();
        if ($calendarService) {
            if ($this->google_event_id || $this->outlook_event_id) {
                $calendarService->updateEvent($this);
            } else {
                $calendarService->createEvent($this);
            }
        }
    }

    public function deleteFromCalendar(): void
    {
        $calendarService = $this->getCalendarService();
        if ($calendarService && ($this->google_event_id || $this->outlook_event_id)) {
            $calendarService->deleteEvent($this);
        }
    }

    protected function getCalendarService()
    {
        if ($this->calendar_type === 'google') {
            return app(GoogleCalendarService::class);
        } elseif ($this->calendar_type === 'outlook') {
            return app(OutlookCalendarService::class);
        }

        return null;
    }

    public function assign(User $user): void
    {
        $this->assigned_to = $user->id;
        $this->save();
    }

    public function markAsComplete(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    public function markAsIncomplete(): void
    {
        $this->status = 'incomplete';
        $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }
}
