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

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->whereFullText(['type', 'description', 'outcome'], $search)
                ->orWhere('date', 'like', '%' . $search . '%');
        });
    }

    public function scopeFilterByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFilterByDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeFilterByActivitableType($query, $type)
    {
        return $query->where('activitable_type', $type);
    }
}