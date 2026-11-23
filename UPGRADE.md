# Upgrade guide

## Upgrading to 5.2.0

### Resources

- **Rename** `UserAttributesConfigContract` to `UserAttributesResourceContract` on your resources and Livewire components.
- **Rename** `getUserAttributesConfig()` to `getUserAttributesOwner()`, or remove it. Without it the owner is the current Filament tenant, or otherwise the logged in user (whichever implements `ConfiguresUserAttributesContract`). You can change that for all resources with:

    ```php
    FilamentUserAttributes::resolveOwnerUsing(fn (string $resource) => auth()->user()?->team);
    ```

    If you forget to rename the method, you'll get an exception instead of silently using another owner.

### Config resource

- Register the config resource with the panel plugin, instead of extending `UserAttributeConfigResource`:

    ```php
    $panel->plugin(FilamentUserAttributesPlugin::make());
    ```

    If you need your own resource, keep extending `UserAttributeConfigResource`, but pass it to the plugin with `->resource(YourResource::class)` and stop the panel from discovering it as well.

### Models

- `UserAttribute::make()` was removed. Assign an array instead: `$model->user_attributes = ['color' => 'red']`.
- `$model->user_attributes` now also returns values that were set but not yet saved.
- `whereUserAttribute()`, `whereUserAttributeContains()` and `whereUserAttributeLength()` are now query scopes. Calling them statically still works, and they can now be chained. Keys with dots now refer to nested values (`'stock.small'`), like `getUserAttributeValue()` does.
- `userAttributeSum()` returns a float when the sum has decimals.
- `UserAttributeConfig::queryByConfig()` and `queryByConfigKey()` are now query scopes.
- The `eager_load_user_attributes` config option now defaults to `true`. If you've published the config file, consider changing it there too.

### Configuring models (e.g. user or tenant)

- `userAttributesConfigs()` now returns a `MorphMany` instead of `HasMany`. This only matters if you implement `ConfiguresUserAttributesContract` yourself instead of using the `ConfiguresUserAttributes` trait.
- If you use a [morph map](https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types), new configurations store the morph alias as `owner_type`. Update existing rows from the class name to the alias:

    ```php
    UserAttributeConfig::where('owner_type', Team::class)->update(['owner_type' => 'team']);
    ```

### Facade

- `FilamentUserAttributes::getUserAttributeConfig($resource)` was renamed to `getResourceOwner($resource)`.
