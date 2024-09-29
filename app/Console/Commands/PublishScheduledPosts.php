<?php

namespace App\Console\Commands;

use App\Models\SocialMediaPost;
use App\Services\FacebookService;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'social-media:publish-scheduled';

    protected $description = 'Publish scheduled social media posts';

    public function handle(FacebookService $facebookService)
    {
        $posts = SocialMediaPost::where('status', SocialMediaPost::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($posts as $post) {
            $this->info("Publishing post ID: {$post->id}");

            try {
                if (in_array('facebook', $post->platforms)) {
                    $facebookService->publishPost($post->content);
                }

                // Add other platform publishing logic here

                $post->markAsPublished();
                $this->info("Post ID: {$post->id} published successfully");
            } catch (\Exception $e) {
                $this->error("Failed to publish post ID: {$post->id}. Error: {$e->getMessage()}");
                $post->markAsFailed();
            }
        }

        $this->info('Finished publishing scheduled posts');
    }
}