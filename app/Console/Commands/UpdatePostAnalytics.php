<?php

namespace App\Console\Commands;

use Exception;
use App\Models\SocialMediaPost;
use App\Services\FacebookService;
use Illuminate\Console\Command;

class UpdatePostAnalytics extends Command
{
    protected $signature = 'social-media:update-analytics';

    protected $description = 'Update analytics for published social media posts';

    public function handle(FacebookService $facebookService)
    {
        $posts = SocialMediaPost::where('status', SocialMediaPost::STATUS_PUBLISHED)
            ->where('updated_at', '<=', now()->subHours(1))
            ->get();

        foreach ($posts as $post) {
            $this->info("Updating analytics for post ID: {$post->id}");

            try {
                if (in_array('facebook', $post->platforms)) {
                    $insights = $facebookService->getPostInsights($post->facebook_post_id);
                    
                    $post->update([
                        'impressions' => $insights['post_impressions'] ?? 0,
                        'engagement' => $insights['post_engagements'] ?? 0,
                    ]);
                }

                // Add other platform analytics logic here

                $this->info("Analytics updated for post ID: {$post->id}");
            } catch (Exception $e) {
                $this->error("Failed to update analytics for post ID: {$post->id}. Error: {$e->getMessage()}");
            }
        }

        $this->info('Finished updating post analytics');
    }
}