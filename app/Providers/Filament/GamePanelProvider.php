<?php

namespace App\Providers\Filament;

use App\Filament\Game\Widgets\ApiKeysList;
use App\Filament\Resources\GameResource\Widgets\GamesWidget;
use App\Http\Middleware\ApplyGameThemeColors;
use App\Models\Game;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GamePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->login()
            ->profile()
            ->id("game")
            ->path("game")
            ->colors([
                "primary" => Color::Amber,
            ])
            ->discoverResources(
                in: app_path("Filament/Game/Resources"),
                for: "App\\Filament\\Game\\Resources",
            )
            ->discoverPages(
                in: app_path("Filament/Game/Pages"),
                for: "App\\Filament\\Game\\Pages",
            )
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(
                in: app_path("Filament/Game/Widgets"),
                for: "App\\Filament\\Game\\Widgets",
            )
            ->widgets([ApiKeysList::class])
            ->navigationItems([
                NavigationItem::make("Admin Panel")
                    ->icon("heroicon-o-adjustments-horizontal")
                    ->url("/admin")
                    ->visible(fn() => auth()->user()?->isAdmin()),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->tenantMiddleware([ApplyGameThemeColors::class])
            ->renderHook(
                "panels::body.end",
                fn(): string => Blade::render("@vite('resources/js/app.js')"),
            )
            ->tenant(Game::class);
    }
}
