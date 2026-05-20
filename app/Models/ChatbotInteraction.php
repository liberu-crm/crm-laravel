<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotInteraction extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'chatbot_id',
        'visitor_id',
        'contact_id',
        'conversation',
        'current_step',
        'completed',
        'converted_to_lead',
        'metadata',
    ];

    protected $casts = [
        'conversation' => 'array',
        'completed' => 'boolean',
        'converted_to_lead' => 'boolean',
        'metadata' => 'array',
    ];

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
