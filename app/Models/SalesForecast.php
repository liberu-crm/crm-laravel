<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesForecast extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'name',
        'period_start',
        'period_end',
        'forecast_type',
        'predicted_revenue',
        'actual_revenue',
        'confidence_level',
        'deal_count',
        'pipeline_id',
        'user_id',
        'team_id',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'predicted_revenue' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'deal_count' => 'integer',
        'metadata' => 'array',
    ];

    const TYPE_PIPELINE = 'pipeline';
    const TYPE_HISTORICAL = 'historical';
    const TYPE_WEIGHTED = 'weighted';
    const TYPE_AI_PREDICTED = 'ai_predicted';

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getAccuracyAttribute(): float
    {
        if (!$this->actual_revenue || !$this->predicted_revenue) {
            return 0;
        }

        $difference = abs($this->actual_revenue - $this->predicted_revenue);
        $accuracy = 100 - (($difference / $this->predicted_revenue) * 100);
        
        return round(max(0, $accuracy), 2);
    }
}
