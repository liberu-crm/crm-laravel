<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'subject',
        'body',
        'status',
        'priority',
        'user_id',
        'email_id',
        'source',
        'source_id',
        'account_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'ticket_id');
    }
}
