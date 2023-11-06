<?php

namespace Luttje\FilamentUserAttributes;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentUserAttributesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-user-attributes';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        FilamentUserAttributes::registerUserAttributeFieldFactories();
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
