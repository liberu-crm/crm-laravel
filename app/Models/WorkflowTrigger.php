<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTrigger extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'workflow_id',
        'type',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    const TYPE_CONTACT_CREATED = 'contact_created';
    const TYPE_CONTACT_UPDATED = 'contact_updated';
    const TYPE_CONTACT_PROPERTY_CHANGED = 'contact_property_changed';
    const TYPE_DEAL_CREATED = 'deal_created';
    const TYPE_DEAL_STAGE_CHANGED = 'deal_stage_changed';
    const TYPE_EMAIL_OPENED = 'email_opened';
    const TYPE_EMAIL_CLICKED = 'email_clicked';
    const TYPE_FORM_SUBMITTED = 'form_submitted';
    const TYPE_PAGE_VIEWED = 'page_viewed';
    const TYPE_TASK_COMPLETED = 'task_completed';
    const TYPE_DATE_PROPERTY = 'date_property';
    const TYPE_SCHEDULE = 'schedule';

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
