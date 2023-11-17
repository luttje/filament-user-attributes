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
     * to 'Product Page'
     */
    'discovery_resource_name_transformer' => Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes::class . '::classNameToLabel',

    /**
     * Whether to eager load the user attributes relationship on models that
     * use the HasUserAttributes trait.
     */
    'eager_load_user_attributes' => false,
];
