<?php

namespace App\Providers\Filament;

use App\Enums\Rol;
use App\Filament\AvatarProviders\Iniciales;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel principal del taller (/panel).
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('taller')
            ->path('panel')
            ->brandName(config('tarzo.nombre_comercial'))
            ->defaultAvatarProvider(Iniciales::class)
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                AppAuthentication::make()->recoverable(),
                // En consola (artisan) no hay usuario: no se consulta la sesión, que exige APP_KEY.
                isRequired: fn (): bool => app()->isProduction() && ! app()->runningInConsole()
                    && (bool) auth()->user()?->hasAnyRole([Rol::Dueno->value, Rol::Contabilidad->value]),
            )
            ->colors([
                'primary' => Color::hex('#0d7a4a'),
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('Recepción'),
                NavigationGroup::make('Cotización'),
                NavigationGroup::make('Piso'),
                NavigationGroup::make('Almacén'),
                NavigationGroup::make('Clientes'),
                NavigationGroup::make('Postventa'),
                NavigationGroup::make('Facturación'),
                NavigationGroup::make('Reportes'),
                NavigationGroup::make('Catálogos'),
                NavigationGroup::make('Administración'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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
