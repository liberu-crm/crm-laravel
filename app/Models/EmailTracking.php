<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTracking extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'email_id',
        'contact_id',
        'tracking_id',
        'subject',
        'sent_at',
        'opened_at',
        'first_opened_at',
        'last_opened_at',
        'open_count',
        'clicked_at',
        'first_clicked_at',
        'last_clicked_at',
        'click_count',
        'bounced_at',
        'bounce_type',
        'bounce_reason',
        'unsubscribed_at',
        'spam_reported_at',
        'user_agent',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'spam_reported_at' => 'datetime',
        'metadata' => 'array',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function recordOpen(string $userAgent = null, string $ipAddress = null): void
    {
        $now = now();
        
        $this->update([
            'opened_at' => $now,
            'first_opened_at' => $this->first_opened_at ?? $now,
            'last_opened_at' => $now,
            'open_count' => $this->open_count + 1,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);
    }

    public function recordClick(string $url, string $userAgent = null, string $ipAddress = null): void
    {
        $now = now();
        
        $this->update([
            'clicked_at' => $now,
            'first_clicked_at' => $this->first_clicked_at ?? $now,
            'last_clicked_at' => $now,
            'click_count' => $this->click_count + 1,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);

        // Create link click record
        EmailLinkClick::create([
            'email_tracking_id' => $this->id,
            'url' => $url,
            'clicked_at' => $now,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);
    }

    public function recordBounce(string $bounceType, string $reason = null): void
    {
        $this->update([
            'bounced_at' => now(),
            'bounce_type' => $bounceType,
            'bounce_reason' => $reason,
        ]);
    }

    public function recordUnsubscribe(): void
    {
        $this->update([
            'unsubscribed_at' => now(),
        ]);
    }

    public function recordSpamReport(): void
    {
        $this->update([
            'spam_reported_at' => now(),
        ]);
    }
}
