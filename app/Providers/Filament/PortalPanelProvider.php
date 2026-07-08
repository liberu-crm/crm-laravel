<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Portal\Widgets\PortalOverview;
use App\Filament\Portal\Widgets\RecentTickets;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Customer-facing portal (G_5). External customers are Users holding the
 * `customer` role (see User::canAccessPanel). Intentionally NON-tenant: a
 * customer has no Jetstream team membership; ticket visibility is scoped by
 * requester (user_id) inside the resource, which is the security boundary here.
 */
class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->login()
            ->passwordReset()
            // Surfaces the notification bell so customers see in-portal alerts
            // (ticket replies #492, document shares) — not just the email copy.
            ->databaseNotifications()
            // Customer-facing branding: a configurable name and top navigation so
            // the portal reads as a product, not the staff admin chrome.
            ->brandName(fn (): string => (string) config('portal.brand_name'))
            ->brandLogo(fn (): ?string => config('portal.logo'))
            ->favicon(fn (): ?string => config('portal.favicon'))
            ->topNavigation()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\\Filament\\Portal\\Resources')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                PortalOverview::class,
                RecentTickets::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
