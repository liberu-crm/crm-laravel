<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallSetting extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'value',
        'description',
    ];
}
