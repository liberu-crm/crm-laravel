<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory;
    use IsTenantModel;
    use Notifiable;

    protected $fillable = [
        'team_id',
        'name',
        'last_name',
        'email',
        'phone_number',
        'status',
        'source',
        'industry',
        'company_size',
        'annual_revenue',
        'lifecycle_stage',
        'company_id',
        'custom_fields',
    ];

    protected $with = ['notes', 'deals', 'activities', 'company'];

    protected $touches = ['team'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
        ];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activitable');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(fn ($contact) => $contact->associateWithCompany());
        static::updating(fn ($contact) => $contact->associateWithCompany());
    }

    protected function associateWithCompany(): void
    {
        if ($this->email && ! $this->company_id) {
            $domain = Str::after($this->email, '@');
            $company = Company::firstOrCreate(
                ['domain' => $domain],
                ['name' => Str::before($domain, '.')]
            );
            $this->company()->associate($company);
        }
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search): void {
            $q->whereFullText(['name', 'last_name', 'email', 'phone_number', 'industry', 'lifecycle_stage'], $search)
                ->orWhere('company_size', 'like', '%'.$search.'%')
                ->orWhere('annual_revenue', 'like', '%'.$search.'%')
                ->orWhereHas('company', fn (Builder $cq) => $cq->whereFullText('name', $search))
                ->orWhere(fn (Builder $cq) => $cq->whereJsonContains('custom_fields', $search));
        });
    }

    public function scopeFilterByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFilterBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeFilterByLifecycleStage(Builder $query, string $stage): Builder
    {
        return $query->where('lifecycle_stage', $stage);
    }

    public function scopeFilterByIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    public function scopeFilterByCompanySize(Builder $query, mixed $size): Builder
    {
        return $query->where('company_size', $size);
    }

    public function scopeFilterByAnnualRevenue(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('annual_revenue', [$min, $max]);
    }
}
