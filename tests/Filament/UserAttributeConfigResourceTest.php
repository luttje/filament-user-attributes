<?php

namespace Luttje\FilamentUserAttributes\Tests\Filament;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Policies\UserAttributeConfigPolicy;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\EditUserAttributeConfig;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages\ManageUserAttributeConfigs;
use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\CategoryResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\SomeSubFolder\ProductResource;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\TagNotSetupResource;
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

it('can configure a richtext input user attribute for a resource', function () {
    $user = User::factory()->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Product Description',
                "config.$id.type" => 'richeditor',
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Product Description',
        'type' => 'richeditor',
    ], $user);
});

it('can configure date, time and datetime input format', function (string $format) {
    $user = User::factory()->create();

    $config = createUserAttributeConfig($this, $user, CategoryResource::class, [
        function ($id, $component) use ($format) {
            $component->fillForm([
                "config.$id.name" => 'custom_attribute_1',
                "config.$id.label" => 'Event Date',
                "config.$id.type" => 'datetime',
                "config.$id.customizations.format" => $format,
                "config.$id.customizations.allow_before_now" => true,
            ]);
        },
    ]);

    assertUserAttributeConfig($config, [
        'name' => 'custom_attribute_1',
        'label' => 'Event Date',
        'type' => 'datetime',
        'customizations' => [
            'format' => $format,
            'allow_before_now' => true,
        ],
    ], $user);
})->with([
    'date' => 'date',
    'time' => 'time',
    'datetime' => 'datetime',
]);

it('can manage user attributes through the action', function () {
    $user = User::factory()->create();

    Config::set('filament-user-attributes.discover_resources', [
        'Resources',
    ]);

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__ . '/../Fixtures/Filament'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures\Filament',
    ));

    $component = Livewire::actingAs($user)
        ->test(ManageUserAttributeConfigs::class);

    $actionName = ucfirst(__('filament-user-attributes::user-attributes.manage_user_attributes'));

    $component
        ->callAction($actionName, [
            'resource_type' => CategoryResource::class,
        ])
        ->assertHasNoActionErrors()
        ->assertRedirect(UserAttributeConfigResource::getUrl('edit', [
            'record' => urlencode(CategoryResource::class),
        ]));
});

it('does not resolve classes that are not configurable resources from the url', function (string $class) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => $class]))
        ->assertNotFound();

    expect(UserAttributeConfig::count())->toBe(0);
})->with([
    'a model' => User::class,
    'a resource without the contract' => TagNotSetupResource::class,
    'a non-existent class' => 'App\DoesNotExist',
]);

it('only resolves manually registered resources when discovery is disabled', function () {
    $user = User::factory()->create();

    Config::set('filament-user-attributes.discover_resources', false);

    FilamentUserAttributes::registerResources([
        ProductResource::class => 'Product Page',
    ]);

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => CategoryResource::class]))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => ProductResource::class]))
        ->assertSuccessful();

    expect(UserAttributeConfig::pluck('resource_type')->all())->toBe([ProductResource::class]);
});

function discoverFixtureResources()
{
    Config::set('filament-user-attributes.discover_resources', [
        'Resources',
    ]);

    FilamentUserAttributes::swap(new FilamentUserAttributesImpl(
        realpath(__DIR__ . '/../Fixtures/Filament'),
        'Luttje\FilamentUserAttributes\Tests\Fixtures\Filament',
    ));
}

it('only lists the configs of the current owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownConfig = $user->createUserAttributeConfig(CategoryResource::class, 'own', 'Own');
    $otherUser->createUserAttributeConfig(CategoryResource::class, 'other', 'Other');

    discoverFixtureResources();

    $this->actingAs($user);

    expect(UserAttributeConfigResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$ownConfig->id]);
});

it('cannot edit the config of another owner by its id', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherConfig = $otherUser->createUserAttributeConfig(CategoryResource::class, 'other', 'Other');

    discoverFixtureResources();

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => $otherConfig->id]))
        ->assertNotFound();
});

it('lists no configs when no config owner can be resolved', function (bool $discover) {
    $user = User::factory()->create();
    $user->createUserAttributeConfig(CategoryResource::class, 'own', 'Own');

    if ($discover) {
        // Resources are found, but nobody is logged in so they resolve no owner
        discoverFixtureResources();
    } else {
        // No configurable resources are found at all
        $this->actingAs($user);
    }

    expect(UserAttributeConfig::count())->toBe(1);
    expect(UserAttributeConfigResource::getEloquentQuery()->count())->toBe(0);
})->with([
    'resources without an owner' => true,
    'no resources' => false,
]);

it('denies access when no policy is registered', function () {
    UserAttributeConfigResource::skipAuthorization(false);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => CategoryResource::class]))
        ->assertForbidden();
});

it('allows access without a policy when authorization is explicitly skipped', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('index'))
        ->assertSuccessful();
});

it('uses the registered policy to decide access', function (string $name, bool $allowed) {
    UserAttributeConfigResource::skipAuthorization(false);
    Gate::policy(UserAttributeConfig::class, UserAttributeConfigPolicy::class);

    $user = User::factory()->create(['name' => $name]);

    $index = $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('index'));
    $edit = $this->actingAs($user)
        ->get(UserAttributeConfigResource::getUrl('edit', ['record' => CategoryResource::class]));

    if ($allowed) {
        $index->assertSuccessful();
        $edit->assertSuccessful();
    } else {
        $index->assertForbidden();
        $edit->assertForbidden();
    }
})->with([
    'allowed by the policy' => ['admin', true],
    'denied by the policy' => ['someone else', false],
]);
