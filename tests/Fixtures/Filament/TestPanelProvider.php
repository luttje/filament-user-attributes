<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TestPanelProvider extends PanelProvider
{
    private static function localPath(string $path): string
    {
        return __DIR__ . '/' . $path;
    }

    public function panel(Panel $panel): Panel
    {
        $id = 'test';
        return $panel
            ->id($id)
            ->path($id)
            ->login()
            // ->registration()
            // ->passwordReset()
            // ->emailVerification()
            ->profile()
            ->default()
            ->discoverResources(in: static::localPath('Resources'), for: 'Luttje\\FilamentUserAttributes\\Tests\\Fixtures\\Filament\\Resources')
            ->discoverPages(in: static::localPath('Pages'), for: 'Luttje\\FilamentUserAttributes\\Tests\\Fixtures\\Filament\\Pages')
            ->discoverWidgets(in: static::localPath('Widgets'), for: 'Luttje\\FilamentUserAttributes\\Tests\\Fixtures\\Filament\\Widgets')
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
            ]);
    }
}
