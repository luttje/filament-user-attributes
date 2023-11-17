<?php

namespace Luttje\FilamentUserAttributes;

use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Luttje\FilamentUserAttributes\Commands\WizardCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentUserAttributesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-user-attributes';

    public static string $viewNamespace = 'filament-user-attributes';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommand(WizardCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('luttje/filament-user-attributes');
            });

        if (file_exists($package->basePath("/../config/filament-user-attributes.php"))) {
            $package->hasConfigFile('filament-user-attributes');
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('filamentUserAttributes', function ($app) {
            return new FilamentUserAttributes();
        });
    }

    public function packageBooted(): void
    {
        \Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes::registerDefaultUserAttributeComponentFactories();

        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );
    }

    protected function getAssetPackageName(): ?string
    {
        return 'luttje/filament-user-attributes';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_user_attributes_table',
            'create_user_attribute_configs_table',
        ];
    }
}
