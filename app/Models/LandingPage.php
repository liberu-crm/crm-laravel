<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'title',
        'name',
        'slug',
        'template',
        'content',
        'settings',
        'metadata',
        'campaign_id',
        'team_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'published_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}