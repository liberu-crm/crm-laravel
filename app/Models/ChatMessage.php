<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'chat_id',
        'sender',
        'sender_id',
        'content',
        'team_id',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(LiveChat::class, 'chat_id');
    }
}
