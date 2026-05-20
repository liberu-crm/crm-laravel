<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->stripe_status === 'active' && !$this->hasExpired();
    }

    public function cancelAt(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }
}