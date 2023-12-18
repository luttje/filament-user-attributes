# 📚 Additional API

Besides using the package as described in the [README](../README.md) you can also use the package in a more custom way. This way you can use this package to easily store variable customizations for models.

## 🖇 Custom usage

* You can easily set custom attributes to your model like this:

    ```php
    $product = new Product();
    $product->user_attributes->color = 'red';
    $product->user_attributes->customizations = [
        'size' => 'large',
        'material' => 'synthetic',
    ];
    $product->save();
    ```

* Getting the attribute is just as easy:

    ```php
    $product = Product::find(1);
    echo $product->user_attributes->color; // 'red'
    echo $product->user_attributes->customizations['material']; // 'synthetic'
    ```

* To display a single user attribute as a column use the `UserAttributeColumn` class:
    ```php
    use Luttje\FilamentUserAttributes\Filament\Tables\UserAttributeColumn;

    //...

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query())
            ->columns([
                UserAttributeColumn::make('color'),
                UserAttributeColumn::make('stock.synthetic'),
            ])
            // ...
    }
    ```

* Destroying all user attributes:

    ```php
    $product = Product::find(1);
    unset($product->user_attributes);
    $product->save();
    ```

> You'll find all the attributes in the `user_attributes` table of your database. However you don't have to worry about it's existence. The `HasUserAttributes` trait handles all the database interactions for you.

## 🔎 Querying by config value

The following snippet shows how to get the config with all related user attributes that have the config value `should_import` set to `true`:

```php
$configs = UserAttributeConfig::queryByConfig('should_import', true)
    ->with('userAttributes.model')
    ->get();

// You'll get all the user attributes, so you still have to filter out those of other tenants.
```

And this snippet checks if there's a config with a `config` key using `queryByConfigKey`, it also limits to the current tenant:

```php
$tenant = user()->tenant ?? Filament::getTenant();
$configs = UserAttributeConfig::queryByConfigKey('import')
    ->where('owner_id', $tenant->id)
    ->where('owner_type', get_class($tenant))
    ->with('userAttributes.model')
    ->get();
```

## 📝 Add custom user attribute configuration fields

You can use `FilamentUserAttributes::registerUserAttributeConfigComponent($callbackOrComponent)` to add custom fields to the user attribute configuration form. This is useful if you want to add custom fields to the user attribute configuration form in `Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource`, but don't want to create a whole new resource for it.

```php
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Support\ServiceProvider;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ...

        FilamentUserAttributes::registerUserAttributeConfigComponent(function(UserAttributeConfig $configModel) {
            if ($configModel->model_type !== Product::class) {
                return null;
            }

            return Fieldset::make(__('Import Settings'))
                ->schema([
                    Checkbox::make('import_enabled')
                        ->label(__('Enable import'))
                        ->default(false)
                        ->live(),
                    TextInput::make('import_name')
                        ->label(__('Name in import file'))
                        ->hidden(fn(Get $get) => !$get('import_enabled'))
                        ->default(''),
                ]);
        });
    }
}
```
