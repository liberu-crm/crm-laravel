<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Request-scoped holder for the IdP logout redirect URL. The Logout event fires
 * inside auth()->logout() — before Filament invalidates the session — so a
 * listener captures the SLO URL here while the id_token / SAML NameID are still
 * readable. LogoutResponse then reads it after invalidation. Bound as a
 * singleton (one instance per request) in AppServiceProvider.
 */
class SsoLogoutState
{
    private ?string $url = null;

    public function set(string $url): void
    {
        $this->url = $url;
    }

    public function url(): ?string
    {
        return $this->url;
    }
}
