<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use App\Traits\MasksFields;
use App\Traits\RestrictsToTerritory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory;
    use IsTenantModel;
    use MasksFields;
    use Notifiable;
    use RestrictsToTerritory;

    /** Sensitive fields masked in serialized output for masked-role viewers. */
    protected $maskedFields = ['email', 'phone_number'];

    protected $fillable = [
        'team_id',
        'territory_id',
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
        'metadata',
    ];

    protected $with = ['notes', 'deals', 'activities', 'company'];

    protected $touches = ['team'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'metadata' => 'array',
            // Encrypted at rest. Queries by value go through the email_hash blind
            // index instead (see hashEmail + the saving hook).
            'email' => 'encrypted',
        ];
    }

    /**
     * Deterministic blind index for email equality lookups + uniqueness. The
     * encrypted column can't be queried or uniquely indexed (random IV), so the
     * hash carries both. Normalised (lowercased) so lookups are case-insensitive.
     */
    public static function hashEmail(string $email): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($email)), (string) config('app.key'));
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

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(fn ($contact) => $contact->associateWithCompany());
        static::updating(fn ($contact) => $contact->associateWithCompany());
        // Keep the blind index in sync with the (plaintext) email on every write.
        static::saving(function ($contact): void {
            $contact->email_hash = filled($contact->email)
                ? static::hashEmail((string) $contact->email)
                : null;
        });
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
            // email is encrypted at rest — match it exactly via the blind index
            // instead of full-text (partial email search is not possible).
            $q->whereFullText(['name', 'last_name', 'phone_number', 'industry', 'lifecycle_stage'], $search)
                ->orWhere('email_hash', static::hashEmail($search))
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
