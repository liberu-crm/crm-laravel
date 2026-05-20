<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveChat extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'visitor_id',
        'contact_id',
        'user_id',
        'status',
        'started_at',
        'ended_at',
        'visitor_name',
        'visitor_email',
        'visitor_ip',
        'visitor_user_agent',
        'visitor_location',
        'page_url',
        'referrer',
        'rating',
        'feedback',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'rating' => 'integer',
        'metadata' => 'array',
    ];

    const STATUS_WAITING = 'waiting';
    const STATUS_ACTIVE = 'active';
    const STATUS_ENDED = 'ended';
    const STATUS_MISSED = 'missed';

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_id');
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->ended_at);
    }
}
