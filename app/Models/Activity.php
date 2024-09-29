<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'type',
        'date',
        'description',
        'outcome',
        'activitable_id',
        'activitable_type',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function activitable(): MorphTo
    {
        return $this->morphTo();
    }
}