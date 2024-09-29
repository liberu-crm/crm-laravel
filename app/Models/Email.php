<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'sender',
        'recipient',
        'subject',

        'content',
        'timestamp',
        'is_sent',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'is_sent' => 'boolean',
    ];
}