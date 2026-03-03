<?php

namespace App\Console\Commands;

use Exception;
use App\Models\SocialMediaPost;
use App\Models\ConnectedAccount;
use App\Services\FacebookService;
use App\Services\TwitterService;
use App\Services\InstagramService;
use App\Services\LinkedInService;
use Illuminate\Console\Command;

class UpdatePostAnalytics extends Command
{
    protected $signature = 'social-media:update-analytics';

    protected $description = 'Update analytics for published social media posts';

    public function handle()
    {
        $posts = SocialMediaPost::where('status', SocialMediaPost::STATUS_PUBLISHED)
            ->where('updated_at', '<=', now()->subHours(1))
            ->get();

        foreach ($posts as $post) {
            $this->info("Updating analytics for post ID: {$post->id}");

            try {
                $analytics = [];

                foreach ($post->platforms as $platform) {
                    $platformAnalytics = $this->fetchPlatformAnalytics($platform, $post);
                    if ($platformAnalytics) {
                        foreach ($platformAnalytics as $key => $value) {
                            $analytics[$key] = ($analytics[$key] ?? 0) + $value;
                        }
                    }
                }

                if (!empty($analytics)) {
                    $post->update($analytics);
                }

                $this->info("Analytics updated for post ID: {$post->id}");
            } catch (Exception $e) {
                $this->error("Failed to update analytics for post ID: {$post->id}. Error: {$e->getMessage()}");
            }
        }

        $this->info('Finished updating post analytics');
    }

    protected function fetchPlatformAnalytics(string $platform, SocialMediaPost $post): array
    {
        $postIds = $post->platform_post_ids ?? [];

        switch ($platform) {
            case 'facebook':
                if (isset($postIds['facebook'])) {
                    $service = new FacebookService();
                    $insights = $service->getPostInsights($postIds['facebook']);
                    return [
                        'impressions' => $insights['post_impressions'] ?? 0,
                    ];
                }
                break;

            case 'twitter':
                $account = ConnectedAccount::ofType('twitter')->primary()->first();
                if ($account) {
                    // Twitter analytics via API requires elevated access; basic stub
                    return [];
                }
                break;

            case 'instagram':
                $account = ConnectedAccount::ofType('instagram')->primary()->first();
                if ($account) {
                    // Instagram insights require a Business account; basic stub
                    return [];
                }
                break;

            case 'linkedin':
                $account = ConnectedAccount::ofType('linkedin')->primary()->first();
                if ($account) {
                    // LinkedIn analytics via UGC Posts API; basic stub
                    return [];
                }
                break;
        }

        return [];
    }
}
