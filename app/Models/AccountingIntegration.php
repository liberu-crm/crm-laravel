<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingIntegration extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'platform',
        'connection_details',
        'last_synced',
    ];

    
    protected $casts = [
        'connection_details' => 'array',
        'last_synced' => 'datetime',
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
