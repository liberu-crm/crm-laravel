<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_server_header_is_not_exposed(): void
    {
        $response = $this->get('/');

        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_health_endpoints_return_ok(): void
    {
        $this->get('/health/live')->assertOk()->assertJson(['status' => 'live']);
        $this->get('/health/startup')->assertOk()->assertJson(['status' => 'starting']);
    }

    public function test_health_ready_endpoint(): void
    {
        $response = $this->get('/health/ready');

        $this->assertContains($response->status(), [200, 503]);
    }

    public function test_api_has_cors_headers_for_preflight(): void
    {
        $response = $this->options('/api/v1/contacts', [], [
            'Origin' => 'http://localhost',
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertHeader('Access-Control-Allow-Methods');
    }
}
