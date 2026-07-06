<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Email;
use App\Models\EmailTracking;
use App\Services\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailTrackingService;
    }

    public function test_create_tracking_persists_row_linked_to_email_and_contact(): void
    {
        $email = Email::factory()->create();
        $contact = Contact::factory()->create();

        $tracking = $this->service->createTracking($email, $contact);

        $this->assertInstanceOf(EmailTracking::class, $tracking);
        $this->assertNotEmpty($tracking->tracking_id);
        $this->assertNotNull($tracking->sent_at);
        $this->assertDatabaseHas('email_trackings', [
            'id' => $tracking->id,
            'email_id' => $email->id,
            'contact_id' => $contact->id,
            'subject' => $email->subject,
        ]);
    }

    public function test_record_open_persists_contact_engagement_metadata(): void
    {
        $email = Email::factory()->create();
        $contact = Contact::factory()->create();
        $tracking = $this->service->createTracking($email, $contact);

        $this->service->recordOpen($tracking->tracking_id);

        $contact->refresh();
        $this->assertSame(1, $contact->metadata['email_engagement']['total_opens']);
        $this->assertSame(1, $contact->metadata['email_engagement']['score']);
    }

    public function test_record_open_marks_tracking_opened_and_returns_true(): void
    {
        $tracking = EmailTracking::factory()->create();

        $result = $this->service->recordOpen($tracking->tracking_id, 'Mozilla/5.0', '1.2.3.4');

        $this->assertTrue($result);

        $tracking->refresh();
        $this->assertNotNull($tracking->opened_at);
        $this->assertNotNull($tracking->first_opened_at);
        $this->assertNotNull($tracking->last_opened_at);
        $this->assertSame(1, $tracking->open_count);
        $this->assertSame('Mozilla/5.0', $tracking->user_agent);
        $this->assertSame('1.2.3.4', $tracking->ip_address);
    }

    public function test_record_open_returns_false_for_unknown_tracking_id(): void
    {
        $this->assertFalse($this->service->recordOpen('00000000-0000-0000-0000-000000000000'));
    }

    public function test_record_open_increments_open_count_on_repeat(): void
    {
        $tracking = EmailTracking::factory()->create();

        $this->service->recordOpen($tracking->tracking_id);
        $this->service->recordOpen($tracking->tracking_id);

        $this->assertSame(2, $tracking->fresh()->open_count);
    }

    public function test_record_open_updates_contact_engagement_without_error(): void
    {
        // Exercises the contact-attached branch (updateContactEngagement).
        $email = Email::factory()->create();
        $contact = Contact::factory()->create();
        $tracking = $this->service->createTracking($email, $contact);

        $this->assertTrue($this->service->recordOpen($tracking->tracking_id));
        $this->assertSame(1, $tracking->fresh()->open_count);
    }

    public function test_record_click_records_link_click_and_returns_true(): void
    {
        $tracking = EmailTracking::factory()->create();

        $result = $this->service->recordClick(
            $tracking->tracking_id,
            'https://example.com/page',
            'Mozilla/5.0',
            '1.2.3.4'
        );

        $this->assertTrue($result);

        $tracking->refresh();
        $this->assertNotNull($tracking->clicked_at);
        $this->assertNotNull($tracking->first_clicked_at);
        $this->assertSame(1, $tracking->click_count);

        $this->assertDatabaseHas('email_link_clicks', [
            'email_tracking_id' => $tracking->id,
            'url' => 'https://example.com/page',
        ]);
    }

    public function test_record_click_returns_false_for_unknown_tracking_id(): void
    {
        $this->assertFalse($this->service->recordClick('nope', 'https://example.com'));
    }

    public function test_signature_is_deterministic_and_url_round_trips(): void
    {
        $tracking = EmailTracking::factory()->create();
        $original = 'https://example.com/path?a=1&b=2';
        $encoded = base64_encode($original);

        $sig = $this->service->generateLinkSignature($tracking->tracking_id, $encoded);

        // Same inputs -> same signature (this is what the controller's hash_equals relies on).
        $this->assertSame($sig, $this->service->generateLinkSignature($tracking->tracking_id, $encoded));
        $this->assertNotEmpty($sig);

        // Encoded URL decodes back to the original.
        $this->assertSame($original, $this->service->decodeTrackedUrl($encoded));
    }

    public function test_tampered_url_produces_a_different_signature(): void
    {
        $tracking = EmailTracking::factory()->create();
        $goodSig = $this->service->generateLinkSignature($tracking->tracking_id, base64_encode('https://example.com'));
        $tamperedSig = $this->service->generateLinkSignature($tracking->tracking_id, base64_encode('https://evil.com'));

        $this->assertNotSame($goodSig, $tamperedSig);
        $this->assertFalse(hash_equals($goodSig, $tamperedSig));
    }

    public function test_decode_invalid_base64_returns_empty_string(): void
    {
        $this->assertSame('', $this->service->decodeTrackedUrl('!!!not-valid-base64!!!'));
    }

    public function test_inject_tracking_pixel_before_closing_body(): void
    {
        $tracking = EmailTracking::factory()->create();
        $pixelUrl = route('email.tracking.pixel', ['tracking_id' => $tracking->tracking_id]);

        $result = $this->service->injectTrackingPixel('<html><body><p>Hi</p></body></html>', $tracking);

        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString($pixelUrl, $result);
        // Pixel is placed before the closing body tag, not after it.
        $this->assertLessThan(strpos($result, '</body>'), strpos($result, '<img'));
    }

    public function test_inject_tracking_pixel_appends_when_no_body_tag(): void
    {
        $tracking = EmailTracking::factory()->create();

        $result = $this->service->injectTrackingPixel('<p>Hi</p>', $tracking);

        $this->assertStringStartsWith('<p>Hi</p><img', $result);
    }

    public function test_replace_links_rewrites_href_to_tracked_url(): void
    {
        $tracking = EmailTracking::factory()->create();
        $html = '<a href="https://example.com/page">Click</a>';

        $result = $this->service->replaceLinksWithTracked($html, $tracking);

        $this->assertStringNotContainsString('href="https://example.com/page"', $result);
        $this->assertStringContainsString('email/track/link/'.$tracking->tracking_id, $result);
    }

    public function test_replace_links_skips_mailto_tel_and_anchors(): void
    {
        $tracking = EmailTracking::factory()->create();
        $html = '<a href="mailto:x@y.com">Mail</a><a href="tel:+123">Call</a><a href="#top">Top</a>';

        $this->assertSame($html, $this->service->replaceLinksWithTracked($html, $tracking));
    }

    public function test_process_email_html_adds_both_tracking_and_rewrites_links(): void
    {
        $tracking = EmailTracking::factory()->create();
        $html = '<html><body><a href="https://example.com">Go</a></body></html>';

        $result = $this->service->processEmailHtml($html, $tracking);

        $this->assertStringContainsString('email/track/link/'.$tracking->tracking_id, $result);
        $this->assertStringContainsString('email/track/pixel/'.$tracking->tracking_id, $result);
        $this->assertStringNotContainsString('href="https://example.com"', $result);
    }
}
