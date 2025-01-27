<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'sender',
        'content',
        'timestamp',
        'priority',
        'status',
        'account_id',
        'thread_id',
        'metadata'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}