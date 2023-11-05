# Filament User Attributes

> **Warning**
> This package is still in development. It is not yet ready for production use.

[![Tests](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml)
[![Fix PHP Code Styling](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml)

Let your users specify custom attributes for models in Filament. This package uses a polymorphic relationship to store the attributes in a JSON column.

## ✨ Features

- [x] Add custom attributes to any model
- [x] Support for UUIDs
- [x] Support for ULIDs
- [ ] Easily display the attributes in a Filament form
- [ ] Easily display the attributes in a Filament table
- [ ] Supported Input types
    - [ ] Text
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
- [ ] Allow users to specify order of attributes
- [ ] User interface for managing user attributes

## 📦 Requirements

- PHP 8.x or higher
- Filament 3.x or higher
- A database that supports JSON columns, e.g:
    - MySQL 5.7.8 or higher
    - PostgreSQL 9.2 or higher
    - SQLite 3.38 or higher

## 🚀 Getting started

1. <s>Install the package via composer:
    
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

2. Run the following command to fully install the package:

    ```bash
    php artisan filament-user-attributes:install
    ```

3. Add the `HasUserAttributes` trait to one or more models you want to have custom user attributes on.

    ```php
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

    class Product extends Model
    {
        use HasUserAttributes;
    }
    ```

## 🛠 Basic usage

In these examples we'll assume you're using the user attributes yourself, to make your own customizations. 

> ⬇ If you want to let your users specify and create which attributes should be added to models, read the [advanced usage](#-advanced-usage) section.

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

## 👩‍💻 Advanced usage

You can let your users configure which attributes should be added to models. You'll present them a form where they can specify the name, type, order and other options for the attribute. You will then update the form and table schema's to automatically display those user configured attributes.

1. Add the `HasUserAttributeConfig` trait to a model you want to be able to configure user attributes. This should be your user or tenant model.

    ```php
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributeConfig;

    class User extends Authenticatable
    {
        // This trait will store the user attributes configuration in relation to this model.
        // Later on we'll use this model to retrieve the configuration.
        use HasUserAttributeConfig;
    }
    ```

2. Have the models with the `HasUserAttributes` trait implement the `HasUserAttributesContract` interface. It should implement `getUserAttributesConfig()` and return the model with the `HasUserAttributeConfig` trait.

    ```php
    use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

    class Product extends Model implements HasUserAttributesContract
    {
        use HasUserAttributes;

        // This will be used to know which user attributes should be shown in the form and table.
        public function getUserAttributesConfig(): HasUserAttributeConfig
        {
            return $this->user;
        }
    }
    ```

3. Update the resources for your models with the `HasUserAttributes` traits to wrap the schema in the `FilamentUserAttributes::form()` and `FilamentUserAttributes::table()` methods:

    ```php
    use Luttje\FilamentUserAttributes\Filament\FilamentUserAttributes;

    // ...

    public static function form(Form $form): Form
    {
        return $form
            ->schema(FilamentUserAttributes::wrapForm([
                // Your other form fields ...

                // wrapForm will use the user attributes configuration to add the user attributes to the form in the correct order.
            ]));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(FilamentUserAttributes::wrapTable([
                // Your other table columns

                // wrapTable will use the user attributes configuration to add the user attributes to the table in the correct order.
            ]));
    }
    ```

4. Finally you need to show the user attributes configuration form somewhere. Here's how you show it in a Filament page:

    ```php
    // TODO
    ```

### Additional methods
* Destroying all user attributes:

    ```php
    $product = Product::find(1);
    unset($product->user_attributes);
    $product->save();
    ```

## ❤ Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details on how to contribute to this project. You'll also find instructions on [how to run the tests](.github/CONTRIBUTING.md#🧪-testing).
