<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey = 'contact_id';

    protected $fillable = [
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

    protected $casts = [
        'custom_fields' => 'array',
    ];

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

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Commented out the useIndex call as the index doesn't exist yet
        // static::addGlobalScope('index_hint', function ($builder) {
        //     $builder->useIndex('contacts_email_index');
        // });

        static::creating(function ($contact) {
            $contact->associateWithCompany();
        });

        static::updating(function ($contact) {
            $contact->associateWithCompany();
        });
    }

    /**
     * Associate the contact with a company based on email domain.
     */
    protected function associateWithCompany()
    {
        if ($this->email) {
            $domain = Str::after($this->email, '@');
            $company = Company::firstOrCreate(['domain' => $domain], ['name' => Str::before($domain, '.')]);
            $this->company()->associate($company);
        }
    }

    /**
     * Scope a query to search contacts based on given criteria.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->whereFullText(['name', 'last_name', 'email', 'phone_number', 'industry', 'lifecycle_stage'], $search)
                ->orWhere('company_size', 'like', '%' . $search . '%')
                ->orWhere('annual_revenue', 'like', '%' . $search . '%')
                ->orWhereHas('company', function ($query) use ($search) {
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

    public function scopeFilterByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeFilterByCompanySize($query, $size)
    {
        return $query->where('company_size', $size);
    }

    public function scopeFilterByAnnualRevenue($query, $min, $max)
    {
        return $query->whereBetween('annual_revenue', [$min, $max]);
    }
}
