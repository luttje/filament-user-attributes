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
use Filament\Forms\Components\Repeater;
use Luttje\FilamentUserAttributes\FilamentUserAttributes as FilamentUserAttributesImpl;

// Configures a user attribute through the management form
function configureUserAttributes($test, $user, $resource, $attributeBuilders)
{
    // Ensure the config is created in the database
    $test->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => $resource]))
        ->assertSuccessful();

    $undoRepeaterFake = Repeater::fake();

    $component = Livewire::actingAs($user)
        ->test(EditUserAttributeConfig::class, ['record' => $resource]);

    foreach ($attributeBuilders as $index => $attributeBuilder) {
        $attributeBuilder($index, $component);
    }

    $component->call('save')
        ->assertHasNoErrors();

    $undoRepeaterFake();

    return $component;
}

function createUserAttributeConfig($test, $user, $resourceType, $attributeBuilders)
{
    configureUserAttributes($test, $user, $resourceType, $attributeBuilders);

    return UserAttributeConfig::where('resource_type', $resourceType)->first();
}

/**
 * Asserts that the user attribute config matches the expected configuration,
 * only checking the keys that are present in the expected config.
 */
function assertUserAttributeConfig(UserAttributeConfig $config, array $expectedConfig, User $user, int $index = 0)
{
    $actualConfig = $config->config[$index];

    // Recursively filter the actual config to only include keys from expected config
    $filteredActual = filterArrayByKeys($actualConfig, $expectedConfig);

    expect($filteredActual)->toMatchArray($expectedConfig);
    expect($config->owner)->toBeObject($user);
}

/**
 * Filters an array by the keys present in another array.
 * This is useful for asserting that only specific keys are present in the actual config.
 *
 * @return array The filtered array containing only keys from the expected array.
 */
function filterArrayByKeys(array $actual, array $expected)
{
    $filtered = [];

    foreach ($expected as $key => $value) {
        if (array_key_exists($key, $actual)) {
            if (is_array($value) && is_array($actual[$key])) {
                // Recursively filter nested arrays
                $filtered[$key] = filterArrayByKeys($actual[$key], $value);
            } else {
                $filtered[$key] = $actual[$key];
            }
        }
    }

    return $filtered;
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

it('can configure a binary input user attribute for a resource', function ($inputType) {
    $user = User::factory()
        ->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) use ($inputType) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Terms and Service',
                "config.$id.type" => $inputType,
                "config.$id.customizations.default" => true,
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Terms and Service',
        'type' => $inputType,
        'customizations' => [
            'default' => true,
        ],
    ], $user);
})->with(['checkbox', 'toggle']);

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

            $component->assertSeeInOrder(['Id', 'Label']);

            $optionId = 0;

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
        realpath(__DIR__ . '/../Fixtures'),
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
        ->assertHasNoFormErrors();
});
