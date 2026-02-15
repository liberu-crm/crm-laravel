<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'workflow_id',
        'type',
        'name',
        'description',
        'config',
        'order',
        'delay_amount',
        'delay_unit',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
        'delay_amount' => 'integer',
    ];

    const TYPE_SEND_EMAIL = 'send_email';
    const TYPE_UPDATE_CONTACT = 'update_contact';
    const TYPE_CREATE_TASK = 'create_task';
    const TYPE_ADD_TAG = 'add_tag';
    const TYPE_REMOVE_TAG = 'remove_tag';
    const TYPE_CHANGE_STAGE = 'change_stage';
    const TYPE_SEND_SMS = 'send_sms';
    const TYPE_CREATE_DEAL = 'create_deal';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_WAIT = 'wait';
    const TYPE_IF_THEN = 'if_then';
    const TYPE_ASSIGN_TO_USER = 'assign_to_user';
    const TYPE_ADD_TO_LIST = 'add_to_list';
    const TYPE_REMOVE_FROM_LIST = 'remove_from_list';

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(WorkflowCondition::class);
    }
}
