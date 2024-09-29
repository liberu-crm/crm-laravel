<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('order');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}