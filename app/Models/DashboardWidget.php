<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'widget_type',
        'position',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}