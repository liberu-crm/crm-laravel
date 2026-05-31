<?php

namespace Tests\Unit\Services;

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteSettingsServiceTest extends TestCase
{
    public function test_clear_removes_cached_settings(): void
    {
        config(['site-settings.cache_key' => 'test_site_settings_cache']);

        $cacheKey = config('site-settings.cache_key');
        Cache::put($cacheKey, 'cached_value', 60);

        $this->assertTrue(Cache::has($cacheKey));

        $service = new SiteSettingsService;
        $service->clear();

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_service_can_be_instantiated(): void
    {
        $service = new SiteSettingsService;

        $this->assertInstanceOf(SiteSettingsService::class, $service);
    }
}
