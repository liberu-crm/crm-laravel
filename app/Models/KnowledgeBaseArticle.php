<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'title',
        'content',
        'category',
        'is_published',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
