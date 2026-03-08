<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'team_id',
        'advertising_account_id',
        'name',
        'external_id',
        'status',
        'objective',
        'budget',
        'budget_type',
        'start_date',
        'end_date',
        'metadata',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function advertisingAccount()
    {
        return $this->belongsTo(AdvertisingAccount::class);
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
