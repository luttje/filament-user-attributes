<?php

// config for luttje/filament-user-attributes
return [
    /**
     * Which directories in App to iterate over to discover resources
     * and livewire components that can have user attributes configured.
     *
     * Set to false if you want to manually register resources using:
     * \Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes::registerResources([
     *   App\Filament\Resources\ProductResource::class => 'Product Page',
     * ])
     */
    'discover_resources' => [
        'Filament',
        'Livewire',
    ],

    /**
     * Which function to use to transform the name for a resource when
     * discovering resources.
     *
     * For example:
     * - 'class_basename' will transform 'App\Filament\Resources\ProductResource'
     *   to 'ProductResource'
     *
     * The default config will transform 'App\Filament\Resources\ProductResource'
     * to the result of Resource::getModelLabel() + Page or fall back to using
     * the class name to end up with to 'Product Page'.
     */
    'discovery_resource_name_transformer' => Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes::class . '::classNameToLabel',

    /**
     * Which directories in App to iterate over to discover models when running
     * the wizard.
     */
    'discover_models' => [
        'Models',
    ],

    /**
     * Whether to eager load the user attributes relationship on models that
     * use the HasUserAttributes trait.
     */
    'eager_load_user_attributes' => false,
];
