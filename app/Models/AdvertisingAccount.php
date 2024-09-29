<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'account_id',
        'access_token',
        'refresh_token',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}