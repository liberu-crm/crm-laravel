<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use HasFactory, IsTenantModel;

    /**
     * Supported event types that can trigger a webhook.
     */
    public const EVENTS = [
        'contact.created',
        'contact.updated',
        'contact.deleted',
        'deal.created',
        'deal.updated',
        'deal.deleted',
        'deal.won',
        'deal.lost',
        'lead.created',
        'lead.updated',
        'lead.converted',
        'task.created',
        'task.completed',
    ];

    protected $fillable = [
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'team_id',
        'last_triggered_at',
        'failure_count',
    ];

    protected $casts = [
        'events'             => 'array',
        'is_active'          => 'boolean',
        'last_triggered_at'  => 'datetime',
        'failure_count'      => 'integer',
    ];

    protected $hidden = ['secret'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
