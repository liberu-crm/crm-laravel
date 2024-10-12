<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'type',
        'status',
        'subject',
        'content',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
