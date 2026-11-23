<?php

namespace Luttje\FilamentUserAttributes\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use Luttje\FilamentUserAttributes\FilamentUserAttributesServiceProvider;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\TestPanelProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        // Before version 9.1.4 of testbench the parent::setUp() method had a call to static::$latestResponse, which caused an error
        // for prefer-lowest testing for PHP 8.2 and Laravel 11.*. This is fixed by just directly calling the setUpTheTestEnvironment() method,
        // which is what the parent::setUp() method does besides setting static::$latestResponse to null.
        $this->setUpTheTestEnvironment();

        // Most tests are not about authorization, those that are turn this off again.
        UserAttributeConfigResource::skipAuthorization();

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
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentUserAttributesServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // An in-memory SQLite database by default, so the tests need no database server and
        // every test starts empty. Set DB_DRIVER (e.g. to mysql) to test against another database.
        $driver = env('DB_DRIVER', env('DB_CONNECTION', 'sqlite'));

        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', $driver === 'sqlite' ? [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ] : [
            'driver' => $driver,
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'test_filament_user_attributes'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ]);

        config()->set('app.env', env('APP_ENV', 'testing'));
        config()->set('app.debug', env('APP_DEBUG', true));
        config()->set('app.key', env('APP_KEY', 'base64:1Md1nEHvllkgVM2HYdStk7utktyuk5MDSClm55PUlLk='));
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
