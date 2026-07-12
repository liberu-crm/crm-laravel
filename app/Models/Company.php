<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use App\Traits\MasksFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    use IsTenantModel;
    use MasksFields;

    /** Sensitive fields masked in serialized output for masked-role viewers. */
    protected $maskedFields = ['phone_number', 'annual_revenue'];

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip',
        'phone_number',
        'website',
        'industry',
        'description',
        'size',
        'location',
        'domain',
        'annual_revenue',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            // Encrypted at rest — not looked up by value (search dropped).
            'phone_number' => 'encrypted',
        ];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
