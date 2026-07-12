<?php

namespace App\Http\Responses;

use App\Support\SsoLogoutState;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements \Filament\Auth\Http\Responses\Contracts\LogoutResponse
{
    public function __construct(private SsoLogoutState $state) {}

    public function toResponse($request): RedirectResponse
    {
        // Single-logout: if a listener captured an IdP end-session URL during the
        // Logout event (before the session was invalidated), redirect there so
        // the IdP session ends too; otherwise the normal local logout.
        return redirect($this->state->url() ?? '/login');
    }
}
