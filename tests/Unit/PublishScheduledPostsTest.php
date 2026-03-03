<?php

namespace Tests\Unit;

use App\Console\Commands\PublishScheduledPosts;
use App\Models\ConnectedAccount;
use App\Models\SocialMediaPost;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\LinkedInService;
use App\Services\TwitterService;
use App\Services\YouTubeService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PublishScheduledPostsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makePost(array $platforms, array $extra = []): SocialMediaPost
    {
        return SocialMediaPost::create(array_merge([
            'content'      => "Line one\nMore detail",
            'platforms'    => $platforms,
            'status'       => SocialMediaPost::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ], $extra));
    }

    public function test_publishes_facebook_post(): void
    {
        $post = $this->makePost(['facebook']);

        $fbMock = Mockery::mock(FacebookService::class);
        $fbMock->shouldReceive('publishPost')->once()->with($post->content)->andReturn(['id' => 'fb_123']);
        $this->app->instance(FacebookService::class, $fbMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $post->fresh()->status);
    }

    public function test_publishes_twitter_post(): void
    {
        $account = ConnectedAccount::factory()->create([
            'account_type' => 'twitter',
            'is_primary'   => true,
            'token'        => 'tok',
            'secret'       => 'sec',
        ]);

        $post = $this->makePost(['twitter']);

        $twMock = Mockery::mock(TwitterService::class);
        $twMock->shouldReceive('postTweet')->once();
        $this->app->instance(TwitterService::class, $twMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $post->fresh()->status);
    }

    public function test_publishes_instagram_post(): void
    {
        $account = ConnectedAccount::factory()->create([
            'account_type' => 'instagram',
            'is_primary'   => true,
            'token'        => 'tok',
        ]);

        $post = $this->makePost(['instagram'], ['image' => 'posts/img.jpg']);

        $igMock = Mockery::mock(InstagramService::class);
        $igMock->shouldReceive('postMedia')->once();
        $this->app->instance(InstagramService::class, $igMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $post->fresh()->status);
    }

    public function test_publishes_linkedin_post(): void
    {
        $account = ConnectedAccount::factory()->create([
            'account_type' => 'linkedin',
            'is_primary'   => true,
            'token'        => 'tok',
        ]);

        $post = $this->makePost(['linkedin']);

        $liMock = Mockery::mock(LinkedInService::class);
        $liMock->shouldReceive('sharePost')->once();
        $this->app->instance(LinkedInService::class, $liMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $post->fresh()->status);
    }

    public function test_publishes_youtube_video(): void
    {
        $account = ConnectedAccount::factory()->create([
            'account_type' => 'youtube',
            'is_primary'   => true,
            'token'        => 'tok',
        ]);

        $post = $this->makePost(['youtube'], ['video' => 'videos/clip.mp4']);

        $ytMock = Mockery::mock(YouTubeService::class);
        $ytMock->shouldReceive('uploadVideo')
            ->once()
            ->with($account, Mockery::any(), 'Line one', $post->content)
            ->andReturn(['id' => 'yt_abc']);
        $this->app->instance(YouTubeService::class, $ytMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $fresh = $post->fresh();
        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $fresh->status);
        $this->assertEquals('yt_abc', $fresh->platform_post_ids['youtube']);
    }

    public function test_skips_youtube_when_no_video_attached(): void
    {
        ConnectedAccount::factory()->create([
            'account_type' => 'youtube',
            'is_primary'   => true,
            'token'        => 'tok',
        ]);

        // No 'video' field set
        $post = $this->makePost(['youtube']);

        $ytMock = Mockery::mock(YouTubeService::class);
        $ytMock->shouldNotReceive('uploadVideo');
        $this->app->instance(YouTubeService::class, $ytMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        // No video to upload → no failure, post marked published (nothing actually failed)
        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $post->fresh()->status);
    }

    public function test_marks_post_failed_when_platform_throws(): void
    {
        $post = $this->makePost(['facebook']);

        $fbMock = Mockery::mock(FacebookService::class);
        $fbMock->shouldReceive('publishPost')->andThrow(new Exception('API error'));
        $this->app->instance(FacebookService::class, $fbMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $this->assertEquals(SocialMediaPost::STATUS_FAILED, $post->fresh()->status);
    }

    public function test_publishes_to_multiple_platforms(): void
    {
        ConnectedAccount::factory()->create([
            'account_type' => 'twitter',
            'is_primary'   => true,
            'token'        => 'tok',
            'secret'       => 'sec',
        ]);

        $post = $this->makePost(['facebook', 'twitter']);

        $fbMock = Mockery::mock(FacebookService::class);
        $fbMock->shouldReceive('publishPost')->once()->andReturn(['id' => 'fb_1']);
        $this->app->instance(FacebookService::class, $fbMock);

        $twMock = Mockery::mock(TwitterService::class);
        $twMock->shouldReceive('postTweet')->once();
        $this->app->instance(TwitterService::class, $twMock);

        $this->artisan('social-media:publish-scheduled')->assertSuccessful();

        $this->assertEquals(SocialMediaPost::STATUS_PUBLISHED, $post->fresh()->status);
    }
}
