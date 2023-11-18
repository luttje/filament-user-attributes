<?php

namespace Luttje\FilamentUserAttributes\Tests\Filament;

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\EditUserAttributeConfig;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\FilamentUserAttributes as FilamentUserAttributesImpl;

// Quick hack to find the id for a user attribute config item
// TODO: Make a test mixin for this
function findIdInComponent($component, $skip = 0)
{
    $html = $component->effects['html'];
    $startText = 'wire:model="data.config.';
    $start = 0;
    $counter = 0;
    $id = null;

    while (($start = strpos($html, $startText, $start)) !== false) {
        $counter++;
        if ($counter == $skip + 1) {
            $end = strpos($html, '.', $start + strlen($startText));
            $id = substr($html, $start + strlen($startText), $end - $start - strlen($startText));
            break;
        }
        $start += strlen($startText);
    }

    return $id;
}

// Configures a user attribute through the management form
// TODO: Make a test mixin for this
function configureUserAttributes($test, $user, $resource, $attributeBuilders)
{
    // Ensure the config is created in the database
    $test->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => $resource]))
        ->assertSuccessful();

    $component = Livewire::actingAs($user)
        ->test(EditUserAttributeConfig::class, ['record' => $resource]);

    foreach ($attributeBuilders as $index => $attributeBuilder) {
        $component->call('mountFormComponentAction', 'data.config', 'add')
            ->assertSee('Common');

        $id = findIdInComponent($component, $index);

        $component->fillForm($attributeBuilder($id));
    }

    $component->call('save')
        ->assertHasNoErrors();
}

it('can configure a text input user attribute for a resource', function () {
    $user = User::factory()
        ->create();

    configureUserAttributes($this, $user, CategoryResource::class, [
        function ($id) {
            return [
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Promotional Text :)',
                "config.$id.type" => 'text',
                "config.$id.customizations.placeholder" => 'Enter your promotional text here',
            ];
        },
    ]);

    $config = UserAttributeConfig::where('resource_type', CategoryResource::class)
        ->first();

    expect($config->config)->toHaveCount(1);
    expect($config->config[0])->toMatchArray([
        'name' => 'custom_attribute_1',
        'label' => 'Promotional Text :)',
        'type' => 'text',
        'customizations' => [
            'placeholder' => 'Enter your promotional text here',
        ],
    ]);
    expect($config->owner)->toBeObject($user);
});

it('can manage user attributes through the action', function () {
    $user = User::factory()
        ->create();

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__.'/../Fixtures'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures',
    ));
    Config::set('filament-user-attributes.discover_resources', [
        'Resources',
    ]);

    Livewire::actingAs($user)
        ->test(ManageUserAttributeConfigs::class)
        ->callAction('Manage user attributes', data: [
            'resource_type' => CategoryResource::class,
        ])
        ->assertSee('Resource type')
        ->assertHasNoActionErrors();
});
