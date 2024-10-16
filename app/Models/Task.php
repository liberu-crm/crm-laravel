<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey = 'task_id';

    protected $fillable = [
        'name',
        'description',
        'due_date',
        'status',
        'contact_id',
        'company_id',
        'opportunity_id',
        'reminder_date',
        'reminder_sent',
        'google_event_id',
        'outlook_event_id',
        'calendar_type',
    ];

    protected $casts = [
        'reminder_date' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function syncWithCalendar()
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

    public function deleteFromCalendar()
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
}
