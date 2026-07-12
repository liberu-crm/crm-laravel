<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_cors_allowed_origins_never_wildcards(): void
    {
        // With credentials enabled, a '*' origin is invalid + unsafe. The config
        // must be an explicit allowlist (empty when unconfigured), never '*'.
        $origins = config('cors.allowed_origins');

        $this->assertIsArray($origins);
        $this->assertNotContains('*', $origins);
        $this->assertTrue((bool) config('cors.supports_credentials'));
    }

    public function test_sso_login_route_is_rate_limited(): void
    {
        $team = Team::factory()->create();
        $url = '/sso/'.$team->id.'/redirect';

        // 30/min limiter — the 31st request in the window is throttled (429),
        // regardless of the per-request outcome (404 with no connection).
        $status = 0;
        for ($i = 0; $i < 32; $i++) {
            $status = $this->get($url)->getStatusCode();
            if ($status === 429) {
                break;
            }
        }

        $this->assertSame(429, $status);
    }
}
