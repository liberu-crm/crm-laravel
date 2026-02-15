<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLinkClick extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'email_tracking_id',
        'url',
        'clicked_at',
        'user_agent',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function emailTracking(): BelongsTo
    {
        return $this->belongsTo(EmailTracking::class);
    }
}
