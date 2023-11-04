# Filament User Attributes

> **Warning**
> This package is still in development. It is not yet ready for production use.

<!--

[![Latest Version on Packagist](https://img.shields.io/packagist/v/luttje/filament-user-attributes.svg?style=flat-square)](https://packagist.org/packages/luttje/filament-user-attributes)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/luttje/filament-user-attributes/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/luttje/filament-user-attributes/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/luttje/filament-user-attributes/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/luttje/filament-user-attributes/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/luttje/filament-user-attributes.svg?style=flat-square)](https://packagist.org/packages/luttje/filament-user-attributes)

-->

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

Make sure you have enabled `pdo_sqlite` in your `php` installation.

1. Find your `php.ini` file
```bash
php --ini
```

2. Open the `php.ini` file and ensure there is no comment (semicolon) before the following line:
```ini
extension=pdo_sqlite
```

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
