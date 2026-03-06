<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageService
{
    /**
     * Languages supported by the application.
     * Key: ISO 639-1 code, Value: display name.
     */
    public const SUPPORTED_LOCALES = [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
    ];

    /**
     * Switch the active application locale.
     *
     * @param  string $locale  ISO 639-1 locale code (e.g. 'en', 'es', 'fr').
     * @throws \InvalidArgumentException  When the locale is not supported.
     */
    public function setLocale(string $locale): void
    {
        if (!$this->isSupported($locale)) {
            throw new \InvalidArgumentException(
                "Locale '{$locale}' is not supported. Supported locales: "
                . implode(', ', array_keys(self::SUPPORTED_LOCALES))
            );
        }

        App::setLocale($locale);
        Session::put('locale', $locale);
    }

    /**
     * Return the currently active locale.
     */
    public function getLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Restore the locale from the session (call in middleware or service provider).
     */
    public function restoreFromSession(): void
    {
        $locale = Session::get('locale', config('app.locale', 'en'));

        if ($this->isSupported($locale)) {
            App::setLocale($locale);
        }
    }

    /**
     * Check whether a given locale code is supported.
     */
    public function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Return an array of all supported locales with their display names.
     *
     * @return array<string, string>
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }
}
