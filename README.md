# Filament User Attributes

> **Warning**
> This package is still in development. It is not yet ready for production use.

[![Tests](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/run-tests.yml)
[![Fix PHP Code Styling](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/luttje/filament-user-attributes/actions/workflows/fix-php-code-styling.yml)

Let your users specify custom attributes for models.

## Requirements

- PHP 8.x or higher
- Filament 3.x or higher
- A database that supports JSON columns, e.g:
    - MySQL 5.7.8 or higher
    - PostgreSQL 9.2 or higher
    - SQLite 3.38 or higher

## Installation

You can install the package via composer:

```bash
composer require luttje/filament-user-attributes
```

> **Note** 
> This package is not yet available on Packagist. You can install it by adding the repository to your `composer.json` file.

### Recommended configuration

After requiring the package with composer simply run the following command to fully install the package:

```bash
php artisan filament-user-attributes:install
```

### Manual configuration

If you want to make changes to the migrations or config. You can publish the following assets.

#### Migrations

```bash
php artisan vendor:publish --tag="filament-user-attributes-migrations"
php artisan migrate
```

#### Config

```bash
php artisan vendor:publish --tag="filament-user-attributes-config"
```

#### Views

```bash
php artisan vendor:publish --tag="filament-user-attributes-views"
```

## Usage

After installation you must add the `HasUserAttributes` trait to the model you want to add user attributes to.

```php
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

class Product extends Model
{
    use HasUserAttributes;
}
```

### Setting attributes

When a user wants to add an attribute to a model, you can use the `setUserAttribute` method.

```php
$product->setUserAttribute('color', 'red');
```

### Retrieving a single attribute

When you want to retrieve a single attribute of a model, you can use the `getUserAttribute` method.

```php
$product->getUserAttribute('color');
```

## Testing

1. Copy `phpunit.xml.example` to `phpunit.xml`

2. Start and create a database that supports JSON columns and add the credentials to the `phpunit.xml` file.

3. Run the tests
```bash
composer test
```

To enable code coverage install [Xdebug](https://xdebug.org/wizard) and configure it in your `php.ini` file:
```ini
[xdebug]
; enables the extension:
zend_extension=xdebug
; required for code coverage:
xdebug.mode=develop,debug,coverage
xdebug.start_with_request = yes
```
Finally run the following command:
```bash
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.
