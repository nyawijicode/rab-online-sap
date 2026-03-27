<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Filament\Pajak\Widgets\PajakStatsOverview;
use App\Filament\Pajak\Widgets\MatchedVendorChart;
use App\Filament\Pajak\Widgets\MismatchedVendorChart;
use App\Filament\Pajak\Widgets\MatchedTrendChart;
use App\Filament\Pajak\Widgets\MismatchedTrendChart;
use App\Filament\Pajak\Widgets\PajakReconciliationStatusChart;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PajakPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pajak')
            ->path('pajak')
            ->homeUrl('/')
            ->login(Login::class)
            ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Pajak/Resources'), for: 'App\\Filament\\Pajak\\Resources')
            ->discoverPages(in: app_path('Filament/Pajak/Pages'), for: 'App\\Filament\\Pajak\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Pajak/Widgets'), for: 'App\\Filament\\Pajak\\Widgets')
            ->widgets([
                PajakReconciliationStatusChart::class,
                PajakStatsOverview::class,
                MatchedVendorChart::class,
                MismatchedVendorChart::class,
                MatchedTrendChart::class,
                MismatchedTrendChart::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])->maxContentWidth('full');
    }
}
