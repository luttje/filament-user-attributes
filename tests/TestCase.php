<?php

namespace Luttje\FilamentUserAttributes\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\FilamentUserAttributesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Luttje\\FilamentUserAttributes\\Database\\Factories\\' . class_basename($modelName) . 'Factory',
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentUserAttributesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'test_filament_user_attributes'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ]);

        config()->set('app.env', env('APP_ENV', 'testing'));
        config()->set('app.key', env('APP_KEY', 'base64:1Md1nEHvllkgVM2HYdStk7utktyuk5MDSClm55PUlLk='));

        FilamentUserAttributes::registerUserAttributeFieldFactories();
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations(['--database' => 'testing']);

        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__ . '/../database/migrations'),
        ]);

        // Test only migrations
        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__ . '/database/migrations'),
        ]);

        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }
}
