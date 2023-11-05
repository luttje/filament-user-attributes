
<div align="center">

# ![Filament User Attributes](./.github/banner.jpeg)

</div>

> **Warning**
> This package is still in development. It is not yet ready for production use, the API may change at any time and it is not yet available on Packagist.

<div align="center">

[![Tests](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml)
[![Fix PHP Code Styling](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml)

</div>

Let your users specify custom attributes for models in Filament. This package uses a polymorphic relationship to store the attributes in a JSON column.

## 🚀 Getting started

1. Make sure your project meets these requirements:
    - PHP 8.1 or higher
    - [Filament 3.0](https://filamentphp.com/docs) or higher
    - A database that supports JSON columns, e.g:
        - MySQL 5.7.8 or higher
        - PostgreSQL 9.2 or higher
        - SQLite 3.38 or higher

2. <s>Install the [package via composer](https://packagist.org/packages/luttje/filament-user-attributes):
    
    ```bash
    composer require luttje/filament-user-attributes
    ```
    </s>

    > **Note** 
    > This package is not yet available on Packagist. You can install it by adding the repository to your `composer.json` file:
    > ```json
    > "repositories": [
    >     {
    >         "type": "vcs",
    >         "url": "https://github.com/luttje/filament-user-attributes"
    >     }
    > ]
    > ```
    > 
    > Then run `composer require luttje/filament-user-attributes @dev`

3. Run the following command to fully install the package:

    ```bash
    php artisan filament-user-attributes:install
    ```

    *This publishes the migrations to create the two required tables and runs them.*

4. Add the `HasUserAttributesContract` interface and `HasUserAttributes` trait to one or more models you want to have custom user attributes on.

    ```php
    use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

    class Product extends Model implements HasUserAttributesContract
    {
        use HasUserAttributes;
    }
    ```

<table align="center">
<tr>
<td align="middle" colspan="2">
<strong>🎉 You're now ready to:</strong>
</td>
</tr>
<tr>
<td align="middle">📎</td>
<td><a href="#📎-minimal-usage">Set and use user attributes yourself</a></td>
</tr>
<tr>
<td align="middle">🖇</td>
<td><a href="#🖇-user-configured-attributes-for-models">Let your users configure which attributes should be added to models</a></td>
</tr>
</table>

## 🛠 Usage

### 📎 Minimal usage

In these examples we'll assume you're using user attributes yourself, to store variable JSON customizations for models.

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

> You'll find all the attributes in the `user_attributes` table of your database. However you don't have to worry about it's existence. The `HasUserAttributes` trait handles all the database interactions for you.

### 🖇 User configured attributes for models

You can let your users configure which attributes should be added to models.

> Through an attribute management form users can choose which model to edit and specify the name, type, order and other options for custom attributes. The attributes will be automatically added to the resource form and table if you follow the steps below.

1. Add the `HasUserAttributesConfigContract` interface and `HasUserAttributesConfig` trait to the model that should be able to configure user attributes (e.g. a user or tenant model):

    ```php
    use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig;

    class User extends Authenticatable implements HasUserAttributesConfigContract
    {
        // This trait will store the user attributes configuration in relation to this model.
        // Later on we'll use this model to retrieve the configuration.
        use HasUserAttributesConfig;
    }
    ```

2. Have the models with the `HasUserAttributes` trait implement the `getUserAttributesConfig()` method to return the model with the `HasUserAttributesConfig` trait.

    ```php
    use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
    use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;
    use Illuminate\Support\Facades\Auth;

    class Product extends Model implements HasUserAttributesContract
    {
        use HasUserAttributes;

        // This is the model that will be asked for the user attributes configuration. For example a user or tenant model.
        public static function getUserAttributesConfig(): ?HasUserAttributesConfigContract
        {
            /** @var \App\Models\User */
            $user = Auth::user();

            return $user;
        }
    }
    ```

3. Go to the resources of all models with user attributes and apply the `HasUserAttributesResource` trait to the resource.

4. In your resources rename the static `form` and `table` methods to become `resourceForm` and `resourceTable` respectively:

    ```php
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributesResource;

    class ProductResource extends Resource
    {
        // This will add the user attributes to the form and table, based on the configuration for the Product model.
        // It will only work if you rename the `form` and `table` methods to `resourceForm` and `resourceTable` respectively.
        use HasUserAttributesResource;

        protected static ?string $model = Product::class;

        // Rename the `form` static method to `resourceForm`:
        public static function resourceForm(Form $form): Form
        {
            return $form
                ->schema([
                    // You add non user attribute fields here as you normally would in the `form` method.
                ])
                ->columns(3); // All form methods function without any changes.
        }

        // Rename the `table` static method to `resourceTable`:
        public static function resourceTable(Table $table): Table
        {
            return $table
                ->columns([
                    // You add non user attribute columns here as you normally would in the `table` method.
                ])
                ->filters([
                    // All table methods function without any changes.
                ]);
        }
    }
    ```

    *Renaming is required so the trait can have control over the form and table.*

Finally you need to show the user attributes configuration form somewhere.

5. Create a resource and inherit from the `UserAttributeConfigResource` class:

    ```php
    namespace App\Filament\Resources;

    use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource as BaseUserAttributeConfigResource;

    class UserAttributeConfigResource extends BaseUserAttributeConfigResource
    {
        protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

        // ...
    }
    ```

    *Or you can create your own resource from scratch. See the [source code](./src/Filament/Resources/) for inspiration.*

### 🗃 Additional methods
* Destroying all user attributes:

    ```php
    $product = Product::find(1);
    unset($product->user_attributes);
    $product->save();
    ```

## ✨ Features

- [x] Add custom attributes to any model
- [x] Support for UUIDs
- [x] Support for ULIDs
- [x] Easily display the attributes in a Filament form
- [x] Easily display the attributes in a Filament table
- [ ] Supported Input types
    - [x] Text
    - [ ] Textarea
    - [ ] Number
        - [ ] Integer
        - [ ] Decimal
        - [ ] Specific range
        - [ ] Specific decimal places
    - [ ] Select
        - [ ] Specific options
        - [ ] From a model
    - [ ] Radio
        - [ ] Specific options
        - [ ] From a model
    - [ ] Date
        - [ ] Date
        - [ ] Time
        - [ ] Date and time
    - [ ] Checkbox
    - [ ] File
        - [ ] Image
        - [ ] PDF
        - [ ] Other
        - [ ] Preview
    - [ ] Color
- [ ] Sensible validations
- [ ] Allow users to specify order of attributes
- [x] User interface for managing user attributes

## ❤ Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details on how to contribute to this project. You'll also find instructions on [how to run the tests](.github/CONTRIBUTING.md#🧪-testing).
