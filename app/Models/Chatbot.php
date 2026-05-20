<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chatbot extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'name',
        'description',
        'welcome_message',
        'fallback_message',
        'is_active',
        'trigger_rules',
        'flow',
        'integrations',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_rules' => 'array',
        'flow' => 'array',
        'integrations' => 'array',
        'metadata' => 'array',
    ];

    public function interactions()
    {
        return $this->hasMany(ChatbotInteraction::class);
    }
}
