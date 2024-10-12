<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormBuilder extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'description',
        'fields',
        'team_id',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
