<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;

class PersonalizationService
{
    /**
     * Segment definitions mapped to lifecycle stages and characteristics.
     */
    protected array $segments = [
        'new_visitor'    => ['lifecycle_stage' => null, 'activity_count' => 0],
        'prospect'       => ['lifecycle_stage' => 'subscriber', 'activity_count' => 1],
        'engaged_lead'   => ['lifecycle_stage' => 'lead', 'activity_count' => 3],
        'marketing_qualified' => ['lifecycle_stage' => 'mql', 'activity_count' => 5],
        'sales_qualified'     => ['lifecycle_stage' => 'sql', 'activity_count' => 8],
        'opportunity'    => ['lifecycle_stage' => 'opportunity', 'activity_count' => 10],
        'customer'       => ['lifecycle_stage' => 'customer', 'activity_count' => null],
    ];

    public function personalizeContent(LandingPage $landingPage, array $userData = null): string
    {
        $content = $landingPage->content;

        // Replace placeholders with user data
        $userData = $userData ?? $this->getUserData();
        foreach ($userData as $key => $value) {
            $content = str_replace("{{$key}}", (string) $value, $content);
        }

        // Advanced personalization: segment-based content blocks
        $segment = $this->determineSegment($userData);
        $content = $this->applySegmentBlocks($content, $segment);

        // Apply conditional content rules
        $content = $this->applyConditionalContent($content, $userData);

        return $content;
    }

    /**
     * Determine which user segment applies based on user data.
     */
    public function determineSegment(array $userData): string
    {
        $lifecycleStage = $userData['lifecycle_stage'] ?? null;
        $activityCount  = (int) ($userData['activity_count'] ?? 0);

        if ($lifecycleStage === 'customer') {
            return 'customer';
        }

        if ($lifecycleStage === 'opportunity') {
            return 'opportunity';
        }

        if ($lifecycleStage === 'sql') {
            return 'sales_qualified';
        }

        if ($lifecycleStage === 'mql' || $activityCount >= 5) {
            return 'marketing_qualified';
        }

        if ($lifecycleStage === 'lead' || $activityCount >= 3) {
            return 'engaged_lead';
        }

        if ($lifecycleStage === 'subscriber' || $activityCount >= 1) {
            return 'prospect';
        }

        return 'new_visitor';
    }

    /**
     * Show or hide content blocks marked with [segment:name]...[/segment] tags.
     * Content blocks are shown only when the segment matches.
     */
    public function applySegmentBlocks(string $content, string $segment): string
    {
        // Handle [segment:name]...[/segment] blocks
        $content = preg_replace_callback(
            '/\[segment:([^\]]+)\](.*?)\[\/segment\]/s',
            function (array $matches) use ($segment) {
                $allowedSegments = array_map('trim', explode(',', $matches[1]));
                return in_array($segment, $allowedSegments, true) ? $matches[2] : '';
            },
            $content
        );

        // Handle [not_segment:name]...[/not_segment] blocks (show when NOT in segment)
        $content = preg_replace_callback(
            '/\[not_segment:([^\]]+)\](.*?)\[\/not_segment\]/s',
            function (array $matches) use ($segment) {
                $excludedSegments = array_map('trim', explode(',', $matches[1]));
                return !in_array($segment, $excludedSegments, true) ? $matches[2] : '';
            },
            $content
        );

        return $content;
    }

    /**
     * Apply conditional content rules based on arbitrary user data values.
     * Syntax: [if:key=value]...[/if]
     */
    public function applyConditionalContent(string $content, array $userData): string
    {
        return preg_replace_callback(
            '/\[if:([^=\]]+)=([^\]]+)\](.*?)\[\/if\]/s',
            function (array $matches) use ($userData) {
                $key      = trim($matches[1]);
                $expected = trim($matches[2]);
                $actual   = (string) ($userData[$key] ?? '');
                return $actual === $expected ? $matches[3] : '';
            },
            $content
        );
    }

    /**
     * Build personalised user data array, enriched from CRM records when available.
     */
    private function getUserData(): array
    {
        if (!Auth::check()) {
            return [
                'name'            => 'Visitor',
                'email'           => '',
                'lifecycle_stage' => null,
                'activity_count'  => 0,
                'industry'        => '',
                'company'         => '',
            ];
        }

        $user = Auth::user();

        // Enrich with contact / lead data when available
        $contact = Contact::where('email', $user->email)->first();
        $lead    = Lead::where('email', $user->email)->first();

        return [
            'name'            => $user->name,
            'email'           => $user->email,
            'lifecycle_stage' => $contact?->lifecycle_stage ?? $lead?->lifecycle_stage,
            'activity_count'  => $contact?->activities()->count() ?? 0,
            'industry'        => $contact?->industry ?? '',
            'company'         => $contact?->company?->name ?? '',
            'first_name'      => $user->name,
            'last_name'       => $contact?->last_name ?? '',
        ];
    }
}