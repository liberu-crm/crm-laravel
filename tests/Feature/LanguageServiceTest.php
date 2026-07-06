<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\LanguageService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Tests\TestCase;

// ponytail: no RefreshDatabase — the service only touches App locale + array session.
class LanguageServiceTest extends TestCase
{
    private LanguageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LanguageService;
    }

    public function test_set_locale_changes_app_locale_and_persists_to_session(): void
    {
        $this->service->setLocale('es');

        $this->assertSame('es', App::getLocale());
        $this->assertSame('es', Session::get('locale'));
    }

    public function test_get_locale_returns_current_locale(): void
    {
        App::setLocale('fr');

        $this->assertSame('fr', $this->service->getLocale());
    }

    public function test_set_locale_rejects_unsupported_locale(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->setLocale('de');
    }

    public function test_set_locale_does_not_change_state_when_locale_unsupported(): void
    {
        App::setLocale('en');

        try {
            $this->service->setLocale('de');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame('en', App::getLocale());
        $this->assertNull(Session::get('locale'));
    }

    public function test_restore_from_session_reapplies_stored_locale(): void
    {
        Session::put('locale', 'fr');
        App::setLocale('en');

        $this->service->restoreFromSession();

        $this->assertSame('fr', App::getLocale());
    }

    public function test_restore_from_session_falls_back_to_config_locale_when_absent(): void
    {
        Session::forget('locale');
        App::setLocale('fr');

        $this->service->restoreFromSession();

        $this->assertSame(config('app.locale'), App::getLocale());
    }

    public function test_restore_from_session_ignores_unsupported_stored_locale(): void
    {
        Session::put('locale', 'de');
        App::setLocale('en');

        $this->service->restoreFromSession();

        // Unsupported stored value is not applied; current locale is left intact.
        $this->assertSame('en', App::getLocale());
    }

    public function test_is_supported_recognises_supported_and_unsupported_locales(): void
    {
        $this->assertTrue($this->service->isSupported('en'));
        $this->assertFalse($this->service->isSupported('de'));
    }

    public function test_get_supported_locales_returns_the_locale_map(): void
    {
        $locales = $this->service->getSupportedLocales();

        $this->assertArrayHasKey('en', $locales);
        $this->assertSame('English', $locales['en']);
    }
}
