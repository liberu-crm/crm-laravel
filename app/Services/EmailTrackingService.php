<?php

namespace App\Services;

use App\Models\EmailTracking;
use App\Models\Email;
use App\Models\Contact;
use Illuminate\Support\Str;

class EmailTrackingService
{
    /**
     * Create a tracking record for an email
     */
    public function createTracking(Email $email, Contact $contact): EmailTracking
    {
        return EmailTracking::create([
            'email_id' => $email->id,
            'contact_id' => $contact->id,
            'tracking_id' => $this->generateTrackingId(),
            'subject' => $email->subject,
            'sent_at' => now(),
        ]);
    }

    /**
     * Generate unique tracking ID
     */
    protected function generateTrackingId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Get tracking pixel URL
     */
    public function getTrackingPixelUrl(EmailTracking $tracking): string
    {
        return route('email.tracking.pixel', ['tracking_id' => $tracking->tracking_id]);
    }

    /**
     * Get tracked link URL
     */
    public function getTrackedLinkUrl(EmailTracking $tracking, string $originalUrl): string
    {
        return route('email.tracking.link', [
            'tracking_id' => $tracking->tracking_id,
            'url' => base64_encode($originalUrl),
        ]);
    }

    /**
     * Inject tracking pixel into email HTML
     */
    public function injectTrackingPixel(string $html, EmailTracking $tracking): string
    {
        $pixelUrl = $this->getTrackingPixelUrl($tracking);
        $pixel = '<img src="' . $pixelUrl . '" width="1" height="1" alt="" style="display:none" />';
        
        // Try to inject before closing body tag
        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixel . '</body>', $html);
        }
        
        // Otherwise, append to the end
        return $html . $pixel;
    }

    /**
     * Replace links in email HTML with tracked links
     */
    public function replaceLinksWithTracked(string $html, EmailTracking $tracking): string
    {
        // Find all links in the HTML
        preg_match_all('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/', $html, $matches);
        
        if (empty($matches[1])) {
            return $html;
        }

        $replacements = [];
        foreach ($matches[1] as $originalUrl) {
            // Skip mailto and tel links
            if (Str::startsWith($originalUrl, ['mailto:', 'tel:', '#'])) {
                continue;
            }
            
            $trackedUrl = $this->getTrackedLinkUrl($tracking, $originalUrl);
            $replacements[$originalUrl] = $trackedUrl;
        }

        // Replace all links
        foreach ($replacements as $original => $tracked) {
            $html = str_replace('href="' . $original . '"', 'href="' . $tracked . '"', $html);
        }

        return $html;
    }

    /**
     * Process email HTML to add tracking
     */
    public function processEmailHtml(string $html, EmailTracking $tracking): string
    {
        $html = $this->replaceLinksWithTracked($html, $tracking);
        $html = $this->injectTrackingPixel($html, $tracking);
        
        return $html;
    }

    /**
     * Record email open
     */
    public function recordOpen(string $trackingId, ?string $userAgent = null, ?string $ipAddress = null): bool
    {
        $tracking = EmailTracking::where('tracking_id', $trackingId)->first();
        
        if (!$tracking) {
            return false;
        }

        $tracking->recordOpen($userAgent, $ipAddress);
        
        // Update contact engagement score
        $this->updateContactEngagement($tracking->contact);
        
        return true;
    }

    /**
     * Record link click
     */
    public function recordClick(string $trackingId, string $url, ?string $userAgent = null, ?string $ipAddress = null): bool
    {
        $tracking = EmailTracking::where('tracking_id', $trackingId)->first();
        
        if (!$tracking) {
            return false;
        }

        $tracking->recordClick($url, $userAgent, $ipAddress);
        
        // Update contact engagement score (clicks are worth more than opens)
        $this->updateContactEngagement($tracking->contact, 2);
        
        return true;
    }

    /**
     * Update contact engagement score based on email interaction
     */
    protected function updateContactEngagement(Contact $contact, int $points = 1): void
    {
        // Get current metadata or initialize
        $metadata = $contact->metadata ?? [];
        
        // Initialize engagement data if not exists
        if (!isset($metadata['email_engagement'])) {
            $metadata['email_engagement'] = [
                'score' => 0,
                'last_interaction' => null,
                'total_opens' => 0,
                'total_clicks' => 0,
            ];
        }

        // Update engagement
        $metadata['email_engagement']['score'] += $points;
        $metadata['email_engagement']['last_interaction'] = now()->toDateTimeString();
        
        if ($points === 1) {
            $metadata['email_engagement']['total_opens']++;
        } else {
            $metadata['email_engagement']['total_clicks']++;
        }

        $contact->update(['metadata' => $metadata]);
    }

    /**
     * Get engagement stats for a contact
     */
    public function getContactEngagementStats(Contact $contact): array
    {
        $tracking = EmailTracking::where('contact_id', $contact->id)->get();
        
        return [
            'total_sent' => $tracking->count(),
            'total_opened' => $tracking->whereNotNull('opened_at')->count(),
            'total_clicked' => $tracking->whereNotNull('clicked_at')->count(),
            'total_bounced' => $tracking->whereNotNull('bounced_at')->count(),
            'open_rate' => $tracking->count() > 0 ? 
                round(($tracking->whereNotNull('opened_at')->count() / $tracking->count()) * 100, 2) : 0,
            'click_rate' => $tracking->count() > 0 ? 
                round(($tracking->whereNotNull('clicked_at')->count() / $tracking->count()) * 100, 2) : 0,
            'engagement_score' => $contact->metadata['email_engagement']['score'] ?? 0,
        ];
    }
}
