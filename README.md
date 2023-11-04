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

4. Now you can easily set custom attributes to your model like this:

    ```php
    $product = new Product();
    $product->user_attributes->color = 'red';
    $product->user_attributes->customizations = [
        'size' => 'large',
        'material' => 'synthetic',
    ];
    $product->save();
    ```

5. Getting the attribute is just as easy:

    ```php
    $product = Product::find(1);
    echo $product->user_attributes->color; // 'red'
    echo $product->user_attributes->customizations['material']; // 'synthetic'
    ```

6. Destroying all user attributes is as easy as:

    ```php
    $product = Product::find(1);
    unset($product->user_attributes);
    $product->save();
    ```

You'll find all the attributes in the `user_attributes` table, but you don't have to worry about that. The `HasUserAttributes` trait handles all the database interactions for you.

### 🧨 Manual configuration

If you want to make changes to the migrations or config. You can publish the following assets.

#### Migrations

```bash
php artisan vendor:publish --tag="filament-user-attributes-migrations"
php artisan migrate
```

#### Views

```bash
php artisan vendor:publish --tag="filament-user-attributes-views"
```

## ❤ Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details on how to contribute to this project. You'll also find instructions on [how to run the tests](.github/CONTRIBUTING.md#🧪-testing).
