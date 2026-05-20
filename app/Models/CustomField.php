<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'type',
        'model_type',
        'team_id',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}