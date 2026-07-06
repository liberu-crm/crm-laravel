<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'call_sid',
        'contact_id',
        'direction',
        'duration',
        'status',
        'team_id',
    ];

    protected $casts = [
        'contact_id' => 'integer',
        'duration' => 'integer',
    ];
}
