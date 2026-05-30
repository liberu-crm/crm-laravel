<?php

namespace Tests\Feature;

use App\Models\EmailTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Uri;
use Tests\TestCase;

class EmailTrackingLinkTest extends TestCase
{
    use RefreshDatabase;

    private function signedRoute(EmailTracking $tracking, string $url): string
    {
        $encoded = base64_encode($url);
        $sig = hash_hmac('sha256', $tracking->tracking_id.':'.$encoded, config('app.key'));

        return route('email.tracking.link', [
            'tracking_id' => $tracking->tracking_id,
            'url' => $encoded,
            's' => $sig,
        ]);
    }

    public function test_redirects_to_relative_url()
    {
        $tracking = EmailTracking::factory()->create();
        $url = '/some/internal/page';

        $response = $this->get($this->signedRoute($tracking, $url));

        $response->assertRedirect($url);
    }

    public function test_redirects_to_same_domain_url()
    {
        $tracking = EmailTracking::factory()->create();
        $appUrl = config('app.url');
        $url = rtrim($appUrl, '/').'/page';

        $response = $this->get($this->signedRoute($tracking, $url));

        $response->assertRedirect($url);
    }

    public function test_redirects_to_subdomain_url()
    {
        $tracking = EmailTracking::factory()->create();
        $appHost = Uri::of(config('app.url'))->host();
        $url = 'https://sub.'.$appHost.'/page';

        $response = $this->get($this->signedRoute($tracking, $url));

        $response->assertRedirect($url);
    }

    public function test_blocks_external_url()
    {
        $tracking = EmailTracking::factory()->create();
        $url = 'https://evil.com/phish';

        $response = $this->get($this->signedRoute($tracking, $url));

        $response->assertRedirect(config('app.url'));
    }

    public function test_blocks_invalid_base64()
    {
        $tracking = EmailTracking::factory()->create();
        $encoded = 'not-valid-base64!!!';
        $sig = hash_hmac('sha256', $tracking->tracking_id.':'.$encoded, config('app.key'));

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->tracking_id,
            'url' => $encoded,
            's' => $sig,
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_blocks_empty_url()
    {
        $tracking = EmailTracking::factory()->create();
        $encoded = '';
        $sig = hash_hmac('sha256', $tracking->tracking_id.':'.$encoded, config('app.key'));

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->tracking_id,
            'url' => $encoded,
            's' => $sig,
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_records_click_with_original_url()
    {
        $tracking = EmailTracking::factory()->create();
        $originalUrl = 'https://evil.com/phish';

        $this->get($this->signedRoute($tracking, $originalUrl));

        $this->assertDatabaseHas('email_trackings', [
            'id' => $tracking->id,
        ]);
    }

    public function test_blocks_missing_signature()
    {
        $tracking = EmailTracking::factory()->create();
        $url = '/internal/page';

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->tracking_id,
            'url' => base64_encode($url),
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_blocks_wrong_signature()
    {
        $tracking = EmailTracking::factory()->create();
        $url = '/internal/page';

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $tracking->tracking_id,
            'url' => base64_encode($url),
            's' => 'invalid-signature',
        ]));

        $response->assertRedirect(config('app.url'));
    }

    public function test_blocks_signature_from_different_tracking()
    {
        $trackingA = EmailTracking::factory()->create();
        $trackingB = EmailTracking::factory()->create();
        $url = '/internal/page';
        $encoded = base64_encode($url);
        $sigB = hash_hmac('sha256', $trackingB->tracking_id.':'.$encoded, config('app.key'));

        $response = $this->get(route('email.tracking.link', [
            'tracking_id' => $trackingA->tracking_id,
            'url' => $encoded,
            's' => $sigB,
        ]));

        $response->assertRedirect(config('app.url'));
    }
}
