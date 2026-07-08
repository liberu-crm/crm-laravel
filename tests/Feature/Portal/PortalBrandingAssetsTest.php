<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use Filament\Facades\Filament;
use Tests\TestCase;

class PortalBrandingAssetsTest extends TestCase
{
    public function test_brand_logo_resolves_from_config(): void
    {
        config(['portal.logo' => 'https://cdn.example.com/logo.png']);

        $this->assertSame('https://cdn.example.com/logo.png', Filament::getPanel('portal')->getBrandLogo());
    }

    public function test_favicon_resolves_from_config(): void
    {
        config(['portal.favicon' => 'https://cdn.example.com/favicon.ico']);

        $this->assertSame('https://cdn.example.com/favicon.ico', Filament::getPanel('portal')->getFavicon());
    }

    public function test_logo_and_favicon_default_to_null_when_unset(): void
    {
        config(['portal.logo' => null, 'portal.favicon' => null]);

        $this->assertNull(Filament::getPanel('portal')->getBrandLogo());
        $this->assertNull(Filament::getPanel('portal')->getFavicon());
    }
}
