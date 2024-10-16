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
        'custom_fields',
        'score',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'potential_value' => 'decimal:2',
        'custom_fields' => 'array',
        'score' => 'integer',
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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function calculateScore(): int
    {
        $score = 0;

        // Score based on potential value
        $score += min(100, $this->potential_value / 1000);

        // Score based on lifecycle stage
        $lifecycleStageScores = [
            'subscriber' => 10,
            'lead' => 20,
            'marketing_qualified_lead' => 40,
            'sales_qualified_lead' => 60,
            'opportunity' => 80,
        ];
        $score += $lifecycleStageScores[$this->lifecycle_stage] ?? 0;

        // Score based on activity count
        $activityCount = $this->activities()->count();
        $score += min(50, $activityCount * 5);

        // Update the score
        $this->update(['score' => $score]);

        return $score;
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

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->whereFullText(['status', 'source', 'lifecycle_stage'], $search)
                ->orWhere('potential_value', 'like', '%' . $search . '%')
                ->orWhere('expected_close_date', 'like', '%' . $search . '%')
                ->orWhereHas('contact', function ($query) use ($search) {
                    $query->whereFullText(['name', 'email'], $search);
                })
                ->orWhereHas('user', function ($query) use ($search) {
                    $query->whereFullText('name', $search);
                })
                ->orWhere(function ($query) use ($search) {
                    $query->whereJsonContains('custom_fields', $search);
                });
        });
    }

    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFilterBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeFilterByLifecycleStage($query, $stage)
    {
        return $query->where('lifecycle_stage', $stage);
    }

    public function scopeFilterByPotentialValue($query, $min, $max)
    {
        return $query->whereBetween('potential_value', [$min, $max]);
    }

    public function scopeFilterByExpectedCloseDate($query, $start, $end)
    {
        return $query->whereBetween('expected_close_date', [$start, $end]);
    }
}