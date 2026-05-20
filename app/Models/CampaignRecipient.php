<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'marketing_campaign_id',
        'recipient_type',
        'recipient_id',
        'email',
        'phone',
        'status',
    ];

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
