<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use App\Traits\MasksFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSet extends Model
{
    use HasFactory;
    use IsTenantModel;
    use MasksFields;

    /** Sensitive fields masked in serialized output for masked-role viewers. */
    protected $maskedFields = ['budget'];

    protected $fillable = [
        'team_id',
        'advertising_account_id',
        'campaign_id',
        'name',
        'external_id',
        'status',
        'budget',
        'budget_type',
        'targeting',
        'metadata',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'targeting' => 'array',
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

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }
}
