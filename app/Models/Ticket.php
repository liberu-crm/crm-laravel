<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
        'status',
        'priority',
        'user_id',
        'email_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}