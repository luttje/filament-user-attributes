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

* Or set multiple at once by assigning an array. The values are merged with the existing user attributes:

    ```php
    $product->user_attributes = ['color' => 'red', 'size' => 'large'];
    $product->save();

    // Mass assignment works too
    Product::create(['name' => 'Shirt', 'user_attributes' => ['color' => 'red']]);
    ```

* Getting the attribute is just as easy. Use dot notation for nested values:

    ```php
    $product = Product::find(1);
    echo $product->user_attributes->color; // 'red'
    echo $product->user_attributes['color']; // 'red'
    echo $product->getUserAttributeValue('customizations.material'); // 'synthetic'

    foreach ($product->user_attributes as $key => $value) {
        // ...
    }

    $product->user_attributes->toArray(); // ['color' => 'red', ...]
    ```

    Values you've set but not yet saved are returned as well.

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

## 🔍 Querying by user attribute

The `HasUserAttributes` trait adds query scopes, which you can use on their own or chain onto any query:

```php
Product::whereUserAttribute('color', 'red')->get();
Product::where('active', true)->whereUserAttribute('stock', '>', 10)->get();
Product::whereUserAttribute('customizations.material', 'synthetic')->get();
Product::whereUserAttributeContains('tags', 'sale')->get();
Product::whereUserAttributeLength('tags', '>', 2)->get();

Product::userAttributeSum('price'); // Sum across all products
Product::allUserAttributes('color'); // Collection of each product's color
```

## 🔎 Querying by config value

The following snippet shows how to get the config with all related user attributes that have the config value `should_import` set to `true`:

```php
$configs = UserAttributeConfig::queryByConfig('should_import', true)
    ->with('userAttributes.model')
    ->get();

// You'll get all the user attributes, so you still have to filter out those of other tenants.
```

And this snippet checks if there's a config with a `config` key using `queryByConfigKey`, it also limits to the current tenant by starting from its configs:

```php
$configs = Filament::getTenant()
    ->userAttributesConfigs()
    ->queryByConfigKey('import')
    ->with('userAttributes.model')
    ->get();
```

## 🌱 Adding user attributes from code

The model with the `ConfiguresUserAttributes` trait (e.g. your tenant) can add user attributes to a resource, for example when seeding defaults for new tenants:

```php
$tenant->createUserAttributeConfig(
    ProductResource::class,
    name: 'color',
    label: 'Color',
    type: 'select',
    customizations: ['options' => [
        ['id' => 'red', 'label' => 'Red'],
        ['id' => 'blue', 'label' => 'Blue'],
    ]],
    options: ['required' => true, 'order_position_form' => 'after', 'order_sibling_form' => 'Name'],
);
```

## 📝 Add custom user attribute configuration fields

You can use `FilamentUserAttributes::registerUserAttributeConfigComponent($callbackOrComponent)` to add custom fields to the user attribute configuration form. This is useful if you want to add custom fields to the user attribute configuration form in `Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource`, but don't want to create a whole new resource for it.

```php
use Filament\Schemas\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
