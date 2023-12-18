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
    ->with('userAttributes')
    ->get();
```

And this snippet checks if there's a config with a `config` key:

```php
$configs = UserAttributeConfig::queryByConfigKey('import')
    ->with('userAttributes')
    ->get();
```
