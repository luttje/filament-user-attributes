<?php

namespace Luttje\FilamentUserAttributes\Tests\Commands;

use Illuminate\Support\Facades\Config;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\FilamentUserAttributes as FilamentUserAttributesImpl;

// Helper to copy directories recursively
function copyDirectory($source, $target)
{
    if (!file_exists($target)) {
        mkdir($target, 0777, true);
    }

    foreach (scandir($source) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $sourcePath = "$source\\$file";
        $destination = "$target\\$file";

        if (is_dir($sourcePath)) {
            copyDirectory($sourcePath, $destination);
        } else {
            copy($sourcePath, $destination);
        }
    }
}

// Helper to copy fixtures to the __DIR__/temp directory for use in tests
function copyFixturesToTemp($fixtureDirectory)
{
    $fixtures = realpath(__DIR__.'/../Fixtures/'.$fixtureDirectory);
    $temp = __DIR__.'/temp/'.$fixtureDirectory;

    copyDirectory($fixtures, $temp);
}

// Helper to copy directories recursively
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                    rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                } else {
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
                }
            }
        }
        rmdir($dir);
    }
}

// Clean up temp after all tests
afterAll(function () {
    $temp = realpath(__DIR__.'/temp');

    if (!file_exists($temp)) {
        return;
    }

    foreach (scandir($temp) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = "$temp/$file";

        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }

    rmdir($temp);
});

it('can render a resource with configured user attributes', function () {
    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/../Fixtures'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures',
    ));

    $this->artisan('filament-user-attributes:wizard')
        ->expectsConfirmation('Do add support for user attributes to your models?', 'no')
        ->expectsConfirmation('Do you want to let a model (like user or tenant) configure user attributes?', 'no')
        ->expectsConfirmation('Do you want to setup any resources to display and edit user attributes?', 'no')
        ->assertExitCode(0);
});

it('can add the desired traits for user attributes to models', function () {
    copyFixturesToTemp('Models');

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/temp'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures',
    ));

    $this->artisan('filament-user-attributes:wizard')
        ->expectsConfirmation('Do add support for user attributes to your models?', 'yes')
        ->expectsQuestion('Which of your models should be able to have user attributes? (comma separated)', [
            'Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category',
            'Luttje\FilamentUserAttributes\Tests\Fixtures\Models\TagNotSetup',
        ])
        ->expectsConfirmation('Do you want to let a model (like user or tenant) configure user attributes?', 'no')
        ->expectsConfirmation('Do you want to setup any resources to display and edit user attributes?', 'no')
        ->assertExitCode(0);

    // Check that category isn't setup double and that tag is setup now
    $contentsCategory = file_get_contents(__DIR__.'/temp/Models/Category.php');
    $contentsTag = file_get_contents(__DIR__.'/temp/Models/TagNotSetup.php');
    $countCategory = substr_count($contentsCategory, 'Traits\HasUserAttributes');
    $countTag = substr_count($contentsTag, 'Traits\HasUserAttributes');

    expect($countCategory)->toBe(1);
    expect($countTag)->toBe(1);
});

it('can add the desired traits to setup a config model', function () {
    copyFixturesToTemp('Models');

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/temp'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures',
    ));

    $this->artisan('filament-user-attributes:wizard')
        ->expectsConfirmation('Do add support for user attributes to your models?', 'no')
        ->expectsConfirmation('Do you want to let a model (like user or tenant) configure user attributes?', 'yes')
        ->expectsQuestion('Which model should configure user attributes?', 'Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Team')
        ->expectsConfirmation('Do you want to setup any resources to display and edit user attributes?', 'no')
        ->assertExitCode(0);

    // Check that team is setup now
    $contentsTeam = file_get_contents(__DIR__.'/temp/Models/Team.php');
    expect($contentsTeam)->toContain('implements \Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract');
    expect($contentsTeam)->toContain('use \Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;');
});

// it('can add the desired traits to setup a resource', function () {
//     copyFixturesToTemp('Models');
//     copyFixturesToTemp('Filament');

//     Config::set('filament-user-attributes.discover_resources', [
//         'Filament\Resources',
//     ]);

//     FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
//         realpath(__DIR__.'/temp'),
//         'Luttje\FilamentUserAttributes\Tests\Fixtures',
//     ));

//     $this->artisan('filament-user-attributes:wizard')
//         ->expectsConfirmation('Do add support for user attributes to your models?', 'no')
//         ->expectsConfirmation('Do you want to let a model (like user or tenant) configure user attributes?', 'no')
//         ->expectsConfirmation('Do you want to setup any resources to display and edit user attributes?', 'yes')
//         ->expectsQuestion('Which resources should display and edit user attributes?', [
//             'Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource',
//             'Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\TagNotSetupResource',
//         ])
//         ->assertExitCode(0);

//     // Check that category isn't setup double and that tag is setup now
//     $contentsCategory = file_get_contents(__DIR__.'/temp/Filament/Resources/CategoryResource.php');
//     $contentsTag = file_get_contents(__DIR__.'/temp/Filament/Resources/TagNotSetupResource.php');
//     $countCategory = substr_count($contentsCategory, 'Traits\UserAttributesResource');
//     $countTag = substr_count($contentsTag, 'Traits\UserAttributesResource');

//     expect($countCategory)->toBe(1);
//     expect($countTag)->toBe(1);
// });
