<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'status',
        'source',
        'potential_value',
        'expected_close_date',
        'contact_id',
        'user_id',
        'lifecycle_stage',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'potential_value' => 'decimal:2',
    ];

    const LIFECYCLE_STAGES = [
        'subscriber',
        'lead',
        'marketing_qualified_lead',
        'sales_qualified_lead',
        'opportunity',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activitable');
    }

    public function setLifecycleStageAttribute($value)
    {
        if (!in_array($value, self::LIFECYCLE_STAGES)) {
            throw new \InvalidArgumentException("Invalid lifecycle stage: {$value}");
        }
        $this->attributes['lifecycle_stage'] = $value;
    }

    public function advanceLifecycleStage()
    {
        $currentIndex = array_search($this->lifecycle_stage, self::LIFECYCLE_STAGES);
        if ($currentIndex !== false && $currentIndex < count(self::LIFECYCLE_STAGES) - 1) {
            $this->lifecycle_stage = self::LIFECYCLE_STAGES[$currentIndex + 1];
            $this->save();
        }
    }
}