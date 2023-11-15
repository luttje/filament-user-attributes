
<div align="center">

![Filament User Attributes](./.github/banner.jpeg)

</div>

> **Warning**
> This package is still in development. It is not yet ready for production use and the API may change at any time.

<div align="center">

[![Tests](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/luttje/filament-user-attributes/badge.svg?branch=main)](https://coveralls.io/github/luttje/filament-user-attributes?branch=main)
[![Fix PHP Code Styling](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml)

</div>

# Filament User Attributes

Let your users specify custom attributes for models in Filament, similar to Custom Fields in WordPress.

## 🚀 Getting started

1. Make sure your project meets these requirements:
    - PHP 8.1 or higher
    - [Livewire 3.0.3](https://livewire.laravel.com/) or higher
    - [Filament 3.0](https://filamentphp.com/docs) or higher
    - A database that supports JSON columns, e.g:
        - MySQL 5.7.8 or higher
        - PostgreSQL 9.2 or higher
        - SQLite 3.38 or higher

2. Install the [package via composer](https://packagist.org/packages/luttje/filament-user-attributes):
    
    ```bash
    composer require luttje/filament-user-attributes
    ```

    > **Note** 
    > This package is currently only available on Packagist with the `dev-main` stability flag. To require this package in your project you need to add the following to your `composer.json` file:
    > ```json
    > "minimum-stability": "dev",
    > "prefer-stable": true,
    > ```

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
<td><a href="#-minimal-usage">Set and use user attributes yourself</a></td>
</tr>
<tr>
<td align="middle">🖇</td>
<td><a href="#-user-configured-attributes-for-models">Let your users configure which attributes should be added to models</a></td>
</tr>
<tr>
<td align="middle">🎈</td>
<td><a href="#filament-livewire-components">Have the custom attribute fields and columns display in a Livewire component</a></td>
</tr>
</table>

## 🛠 Usage

### 📎 Minimal usage

In these examples we'll assume you're using user attributes as a developer, to easily store variable customizations for models.

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

You can let your users configure which attributes (custom fields) should be added to your filament tables and forms.

> Through an attribute management form users can choose which model to edit and specify the name, type, order and other options for custom attributes.
> ![](./.github/screenshot-management-form.png)
> The attributes can then be added to the resource form and optionally the table:
> ![](./.github/screenshot-resulting-form.png)

1. Add the `ConfiguresUserAttributesContract` interface and `ConfiguresUserAttributes` trait to the model that should be able to configure user attributes (e.g. a user or tenant model):

    ```php
    use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
    use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

    class User extends Authenticatable implements ConfiguresUserAttributesContract
    {
        // This trait will store the user attributes configurations for each user. Meaning each user can specify different attributes to appear on forms and tables for them.
        // In most cases you'll want this on a Tenant, Team or something similar.
        use ConfiguresUserAttributes;
    }
    ```

Now it's time to setup a resource that should display the user attributes and allow editting them:

2. Have the resource use the `UserAttributesResource` trait.

3. In your resource wrap the array for your fields (in the `form` method) in `self::withUserAttributeFields()`.

4. Similarly wrap the array for your columns (in the `table` method) in `self::withUserAttributeColumns()`.

5. Have the resource implement the `UserAttributesConfigContract` method `getUserAttributesConfig()` to return the model instance that decides which custom user attributes are available. This is the model with the `ConfiguresUserAttributes` trait (like the user or tenant).

**Your resource should now look something like this:**

```php
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductResource extends Resource implements UserAttributesConfigContract
{
    // This will add the user attributes to the form and table, based on the configuration for the Product model, specified by the User.
    use UserAttributesResource;

    protected static ?string $model = Product::class;

    // This is the model that will be asked for the user attributes configuration. For example a user or tenant model.
    public static function getUserAttributesConfig(): ?ConfiguresUserAttributesContract
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        return $user;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                // Wrap your schema with:
                self::withUserAttributeFields([
                    // You add non user attribute fields here as you normally would in the `form` method, e.g:
                    TextInput::make('name'),
                ])
            )
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                // Wrap your columns with:
                self::withUserAttributeColumns([
                    // You add non user attribute columns here as you normally would in the `table` method, e.g:
                    Tables\Columns\TextColumn::make('name')
                        ->sortable()
                        ->searchable()
                ])
            )
            ->filters([
                // etc...
            ]);
    }
}
```

Finally you need to show the user attributes configuration form somewhere. That way users can actually configure their custom attributes for the resource.

6. Create a resource and inherit from the `UserAttributeConfigResource` class:

    ```php
    // app/Filament/Resources/UserAttributeConfigResource.php
    namespace App\Filament\Resources;

    use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource as BaseUserAttributeConfigResource;

    class UserAttributeConfigResource extends BaseUserAttributeConfigResource
    {
        protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    }
    ```

    *Or you can create your own resource from scratch. See the [source code](./src/Filament/Resources/) for inspiration.*

### 🗃 Additional examples

#### Filament Livewire Components

Filament Livewire components work roughly the same. We also implement the `UserAttributesConfigContract` method `getUserAttributesConfig` so the configuration is retrieved from the model that specifies configurations.

```php
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductManageComponent extends Component implements HasForms, HasTable, UserAttributesConfigContract
{
    use UserAttributesResource;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getUserAttributesConfig(): ?ConfiguresUserAttributesContract
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        return $user;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query())
            ->columns(
                // Wrap your columns with:
                self::withUserAttributeColumns([
                    TextColumn::make('slug'),
                    TextColumn::make('name'),
                ])
            );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(
                // Wrap your schema with:
                self::withUserAttributeFields([
                    TextInput::make('name'),
                ])
            )
            ->statePath('data')
            ->model(Product::class);
    }
}
```
*For a complete example of a Livewire component see [the test mock component here](https://github.com/luttje/filament-user-attributes/blob/main/tests/Fixtures/Livewire/ConfiguredManageComponent.php).*

#### Additional methods

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
- [x] Sensible validations for input types
- [x] Allow users to specify order of attributes
    - [x] In the form
    - [x] In the table
- [x] Allow users to hide attributes
    - [x] In the form
    - [x] In the table
- [x] User interface for managing user attributes
- [x] Support for Tabs and Sections in the form

**Supported Input types:**

- [x] Text
- [x] Textarea
- [x] Number
    - [x] Integer
    - [x] Decimal
    - [x] Specific range
    - [x] Specific decimal places
- [x] Select
    - [x] Specific options
    - [ ] From an existing model property
- [x] Radio
    - [x] Specific options
    - [ ] From an existing model property
- [x] Tags
    - [x] With suggestions
- [x] Date
    - [x] Date
    - [x] Time
    - [x] Date and time
- [x] Checkbox
    - [x] With default
- [ ] File
    - [ ] Image
    - [ ] PDF
    - [ ] Other
    - [ ] Preview
- [ ] Color

## ❤ Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details on how to contribute to this project. You'll also find instructions on [how to run the tests](.github/CONTRIBUTING.md#🧪-testing).
