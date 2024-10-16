<?php

namespace App\Models;

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
        static::addGlobalScope('index_hint', function ($builder) {
            $builder->useIndex('contacts_email_index');
        });

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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('phone_number', 'like', '%' . $search . '%')
                ->orWhere('company_size', 'like', '%' . $search . '%')
                ->orWhere('industry', 'like', '%' . $search . '%')
                ->orWhere('lifecycle_stage', 'like', '%' . $search . '%')
                ->orWhere('annual_revenue', 'like', '%' . $search . '%')
                ->orWhere(function ($query) use ($search) {
                    $query->whereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                })
                ->orWhereHas('company', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->orWhere(function ($query) use ($search) {
                    $query->whereJsonContains('custom_fields', $search);
                });
        });
    }
}
