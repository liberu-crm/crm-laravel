<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadForm extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'fields',
        'landing_page_id',
    ];

    protected $casts = [
        'fields' => 'json',
    ];

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }
}