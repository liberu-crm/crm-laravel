<?php

namespace Tests\Feature;

use App\Models\EmailTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTrackingLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_relative_url()
    {
        $tracking = EmailTracking::factory()->create();
        $url = '/some/internal/page';

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => base64_encode($url),
        ]));

        $response->assertRedirect($url);
    }

    public function test_redirects_to_same_domain_url()
    {
        $tracking = EmailTracking::factory()->create();
        $appUrl = config('app.url');
        $url = rtrim($appUrl, '/') . '/page';

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => base64_encode($url),
        ]));

        $response->assertRedirect($url);
    }

    public function test_redirects_to_subdomain_url()
    {
        $tracking = EmailTracking::factory()->create();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $url = 'https://sub.' . $appHost . '/page';

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => base64_encode($url),
        ]));

        $response->assertRedirect($url);
    }

    public function test_blocks_external_url()
    {
        $tracking = EmailTracking::factory()->create();
        $url = 'https://evil.com/phish';

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => base64_encode($url),
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_blocks_invalid_base64()
    {
        $tracking = EmailTracking::factory()->create();

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => 'not-valid-base64!!!',
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_blocks_empty_url()
    {
        $tracking = EmailTracking::factory()->create();

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => '',
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_records_click_with_original_url()
    {
        $tracking = EmailTracking::factory()->create();
        $originalUrl = 'https://evil.com/phish';

        $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->id,
            'url' => base64_encode($originalUrl),
        ]));

        $this->assertDatabaseHas('email_trackings', [
            'id' => $tracking->id,
        ]);
    }
}
