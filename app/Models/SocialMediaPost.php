<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMediaPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'scheduled_at',
        'platforms',
        'status',
        'likes',
        'shares',
        'comments',
        'clicks',
        'reach',
        'impressions',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'platforms' => 'array',
        'likes' => 'integer',
        'shares' => 'integer',
        'comments' => 'integer',
        'clicks' => 'integer',
        'reach' => 'integer',
        'impressions' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_PUBLISHED,
            self::STATUS_FAILED,
        ];
    }

    public function isScheduled()
    {
        return $this->status === self::STATUS_SCHEDULED && $this->scheduled_at > now();
    }

    public function isPublishable()
    {
        return $this->status === self::STATUS_SCHEDULED && $this->scheduled_at <= now();
    }

    public function markAsPublished()
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->save();
    }

    public function markAsFailed()
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }
}