<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisingAccount extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'platform',
        'account_id',
        'access_token',
        'refresh_token',
        'status',
        'last_sync',
        'metadata',
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_sync' => 'datetime',
        'metadata' => 'array',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function adSets()
    {
        return $this->hasMany(AdSet::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }
}
