<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'team_id',
        'user_id',
        'message_id',
        'sender',
        'recipient',
        'subject',
        'body',
        'from',
        'to',
        'cc',
        'bcc',
        'status',
        'scheduled_at',
        'sent_at',
        'opened_at',
        'clicked_at',
        'email_template_id',
        'campaign_id',
        'metadata',
        'content',
        'timestamp',
        'is_sent',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'is_sent' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'metadata' => 'array',
    ];
}
