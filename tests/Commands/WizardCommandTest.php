<?php

namespace Luttje\FilamentUserAttributes\Tests\Commands;

use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\FilamentUserAttributes as FilamentUserAttributesImpl;

// Helper to copy fixtures to the __DIR__/temp directory for use in tests
function copyFixturesToTemp($fixtureDirectory)
{
    $fixtures = realpath(__DIR__.'/../Fixtures/'.$fixtureDirectory);
    $temp = __DIR__.'/temp/'.$fixtureDirectory;

    if (!file_exists($temp)) {
        mkdir($temp, 0777, true);
    }

    foreach (scandir($fixtures) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $source = "$fixtures/$file";
        $destination = "$temp/$file";

        copy($source, $destination);
    }
}

// Clean up temp after all tests
afterAll(function () {
    // $temp = realpath(__DIR__.'/temp');

    // if (!file_exists($temp)) {
    //     return;
    // }

    // foreach (scandir($temp) as $file) {
    //     if ($file === '.' || $file === '..') {
    //         continue;
    //     }

    //     $path = "$temp/$file";

    //     if (is_dir($path)) {
    //         rmdir($path);
    //     } else {
    //         unlink($path);
    //     }
    // }

    // rmdir($temp);
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

it('can add the desired traits for user attributes to models', function() {
    copyFixturesToTemp('Models');

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/temp'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures',
    ));

    $this->artisan('filament-user-attributes:wizard')
        ->expectsConfirmation('Do add support for user attributes to your models?', 'yes')
        ->expectsQuestion('Which of your models should be able to have user attributes? (comma separated)', [
            'Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Category',
            'Luttje\FilamentUserAttributes\Tests\Fixtures\Models\Product',
        ])
        ->expectsConfirmation('Do you want to let a model (like user or tenant) configure user attributes?', 'no')
        ->expectsConfirmation('Do you want to setup any resources to display and edit user attributes?', 'no')
        ->assertExitCode(0);
});
