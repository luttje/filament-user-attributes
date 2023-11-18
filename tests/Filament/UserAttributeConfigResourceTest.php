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
// !Repeaters are hard to test, consider: https://github.com/filamentphp/filament/discussions/6070
function findIdInComponent($component, $skip, $suffix = '.')
{
    $html = $component->effects['html'];
    $prefix = 'wire:model="data.config' . $suffix;
    $counter = 0;
    $foundIds = [];

    do {
        $line = substr($html, 0, strpos($html, "\n"));
        if (strpos($line, $prefix) !== false) {
            preg_match('/' . $prefix . '([^.]+)/', $line, $matches);
            $id = $matches[1];

            if (!in_array($id, $foundIds)) {
                $foundIds[] = $id;
                $counter++;
            }

            if ($counter > $skip) {
                return $id;
            }
        }

        $html = substr($html, strpos($html, "\n") + 1);
    } while (strpos($html, $prefix) !== false);

    return null;
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

        $attributeBuilder($id, $component);
    }

    $component->call('save')
        ->assertHasNoErrors();

    return $component;
}

function createUserAttributeConfig($test, $user, $resourceType, $attributeBuilders)
{
    configureUserAttributes($test, $user, $resourceType, $attributeBuilders);

    return UserAttributeConfig::where('resource_type', $resourceType)->first();
}

function assertUserAttributeConfig($config, $expectedConfig, $user, $index = 0)
{
    expect($config->config[$index])->toMatchArray($expectedConfig);
    expect($config->owner)->toBeObject($user);
}

it('can configure a text(area) input user attribute for a resource', function ($inputType) {
    $user = User::factory()->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) use ($inputType) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Promotional Text :)',
                "config.$id.type" => $inputType,
                "config.$id.customizations.placeholder" => 'Enter your promotional text here',
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Promotional Text :)',
        'type' => $inputType,
        'customizations' => [
            'placeholder' => 'Enter your promotional text here',
        ],
    ], $user);
})->with([
    'text' => 'text',
    'textarea' => 'textarea',
]);

it('can configure a richtext input user attribute for a resource', function () {
    $user = User::factory()
        ->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Promotional Text :)',
                "config.$id.type" => 'richtext',
                "config.$id.customizations.placeholder" => 'Enter your promotional text here',
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Promotional Text :)',
        'type' => 'richtext',
    ], $user);
});

it('can configure a number input with min, max and decimal places', function () {
    $user = User::factory()
        ->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Price',
                "config.$id.type" => 'number',
                "config.$id.customizations.minimum" => 0,
                "config.$id.customizations.maximum" => 100,
                "config.$id.customizations.decimal_places" => 2,
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Price',
        'type' => 'number',
        'customizations' => [
            'minimum' => 0,
            'maximum' => 100,
            'decimal_places' => 2,
        ],
    ], $user);
});

it('can configure a checkbox input user attribute for a resource', function () {
    $user = User::factory()
        ->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Terms and Service',
                "config.$id.type" => 'checkbox',
                "config.$id.customizations.default" => true,
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Terms and Service',
        'type' => 'checkbox',
        'customizations' => [
            'default' => true,
        ],
    ], $user);
});

it('can configure a select/radio input user attribute for a resource', function (string $inputType) {
    $user = User::factory()
        ->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) use ($inputType) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Category',
                "config.$id.type" => $inputType,
            ]);

            $component//->call('mountFormComponentAction', "data.config.$id.customizations.options", 'add')
                ->assertSeeInOrder(['Id', 'Label']);

            $optionId = findIdInComponent($component, 0, ".$id.customizations.options.");

            $component->fillForm([
                "config.$id.customizations.options.$optionId.id" => 'option_1',
                "config.$id.customizations.options.$optionId.label" => 'Option 1',
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Category',
        'type' => $inputType,
        'customizations' => [
            'options' => [
                [
                    'id' => 'option_1',
                    'label' => 'Option 1',
                ]
            ],
        ],
    ], $user);
})->with([
    'select' => 'select',
    'radio' => 'radio',
]);

it('can configure date, time and datetime input format', function () {
    $user = User::factory()
        ->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Date',
                "config.$id.type" => 'datetime',
                "config.$id.customizations.format" => 'date',
            ]);
        },
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_2',
                "config.$id.label" => 'Time',
                "config.$id.type" => 'datetime',
                "config.$id.customizations.format" => 'time',
            ]);
        },
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_3',
                "config.$id.label" => 'Datetime',
                "config.$id.type" => 'datetime',
                "config.$id.customizations.format" => 'datetime',
                "config.$id.customizations.allow_before_now" => true,
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Date',
        'type' => 'datetime',
        'customizations' => [
            'format' => 'date',
            'allow_before_now' => false,
        ],
    ], $user);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_2',
        'label' => 'Time',
        'type' => 'datetime',
        'customizations' => [
            'format' => 'time',
            'allow_before_now' => false,
        ],
    ], $user, 1);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_3',
        'label' => 'Datetime',
        'type' => 'datetime',
        'customizations' => [
            'format' => 'datetime',
            'allow_before_now' => true,
        ],
    ], $user, 2);
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
