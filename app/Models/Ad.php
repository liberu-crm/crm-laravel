<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'advertising_account_id',
        'campaign_id',
        'ad_set_id',
        'name',
        'external_id',
        'status',
        'headline',
        'description',
        'destination_url',
        'creative_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function advertisingAccount()
    {
        return $this->belongsTo(AdvertisingAccount::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function adSet()
    {
        return $this->belongsTo(AdSet::class);
    }
}
