<?php

namespace App\Models;

use App\Contracts\OwnsRecords;
use App\Traits\IsTenantModel;
use App\Traits\RestrictsToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

class Lead extends Model implements OwnsRecords
{
    use HasFactory;
    use IsTenantModel;
    use Notifiable;
    use RestrictsToOwner;

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
        'team_id',
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

    /**
     * Calculate this lead's score and persist it to the `score` column.
     *
     * Scoring rules (each term added, then the total clamped to 0..100):
     *   - source:          referral +30, website +20, social_media +10, otherwise +5
     *   - potential_value: + min(30, floor(potential_value / 1000))
     *   - contact present: +15
     *   - lifecycle_stage: customer +25, opportunity +20, lead +10, otherwise 0
     *
     * The maximum reachable total is exactly 100 (30 + 30 + 15 + 25); the clamp
     * guards against future rule changes and negative/oversized inputs.
     */
    public function calculateScore(): int
    {
        $score = match ($this->source) {
            'referral' => 30,
            'website' => 20,
            'social_media' => 10,
            default => 5,
        };

        $score += (int) min(30, floor((float) $this->potential_value / 1000));

        if ($this->contact_id !== null) {
            $score += 15;
        }

        $score += match ($this->lifecycle_stage) {
            'customer' => 25,
            'opportunity' => 20,
            'lead' => 10,
            default => 0,
        };

        $score = (int) max(0, min(100, $score));

        $this->update(['score' => $score]);

        return $score;
    }

    public function setLifecycleStageAttribute($value): void
    {
        if (! in_array($value, self::LIFECYCLE_STAGES)) {
            throw new InvalidArgumentException("Invalid lifecycle stage: {$value}");
        }
        $this->attributes['lifecycle_stage'] = $value;
    }

    public function advanceLifecycleStage(): void
    {
        $currentIndex = array_search($this->lifecycle_stage, self::LIFECYCLE_STAGES);
        if ($currentIndex !== false && $currentIndex < count(self::LIFECYCLE_STAGES) - 1) {
            $this->lifecycle_stage = self::LIFECYCLE_STAGES[$currentIndex + 1];
            $this->save();
        }
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search): void {
            $query->whereFullText(['status', 'source', 'lifecycle_stage'], $search)
                ->orWhere('potential_value', 'like', '%'.$search.'%')
                ->orWhere('expected_close_date', 'like', '%'.$search.'%')
                ->orWhereHas('contact', function ($query) use ($search): void {
                    $query->whereFullText(['name', 'email'], $search);
                })
                ->orWhereHas('user', function ($query) use ($search): void {
                    $query->whereFullText('name', $search);
                })
                ->orWhere(function ($query) use ($search): void {
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
