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

class PublishScheduledPosts extends Command
{
    protected $signature = 'social-media:publish-scheduled';

    protected $description = 'Publish scheduled social media posts';

    public function handle()
    {
        $posts = SocialMediaPost::where('status', SocialMediaPost::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($posts as $post) {
            $this->info("Publishing post ID: {$post->id}");

            $allSucceeded = true;

            foreach ($post->platforms as $platform) {
                try {
                    $this->publishToPlatform($platform, $post);
                    $this->info("Post ID: {$post->id} published to {$platform}");
                } catch (Exception $e) {
                    $this->error("Failed to publish post ID: {$post->id} to {$platform}. Error: {$e->getMessage()}");
                    $allSucceeded = false;
                }
            }

            if ($allSucceeded) {
                $post->markAsPublished();
                $this->info("Post ID: {$post->id} published successfully");
            } else {
                $post->markAsFailed();
            }
        }

        $this->info('Finished publishing scheduled posts');
    }

    protected function publishToPlatform(string $platform, SocialMediaPost $post): void
    {
        switch ($platform) {
            case 'facebook':
                $service = new FacebookService();
                $result = $service->publishPost($post->content);
                if ($result) {
                    $ids = $post->platform_post_ids ?? [];
                    $ids['facebook'] = $result['id'] ?? null;
                    $post->platform_post_ids = $ids;
                    $post->save();
                }
                break;

            case 'twitter':
                $account = ConnectedAccount::ofType('twitter')->primary()->first();
                if ($account) {
                    $service = new TwitterService();
                    $service->postTweet($account, $post->content);
                }
                break;

            case 'instagram':
                $account = ConnectedAccount::ofType('instagram')->primary()->first();
                if ($account) {
                    $service = new InstagramService();
                    $service->postMedia($account, $post->image, $post->content);
                }
                break;

            case 'linkedin':
                $account = ConnectedAccount::ofType('linkedin')->primary()->first();
                if ($account) {
                    $service = new LinkedInService();
                    $service->sharePost($account, $post->content);
                }
                break;

            default:
                throw new Exception("Unsupported platform: {$platform}");
        }
    }
}
