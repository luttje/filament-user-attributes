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

## Installation

You can install the package via composer:

```bash
composer require luttje/filament-user-attributes
```

> **Note** 
> This package is not yet available on Packagist. You can install it by adding the repository to your `composer.json` file.

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-user-attributes-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-user-attributes-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-user-attributes-views"
```

This is the contents of the published config file:

```php
return [
    // TODO
];
```

## Usage

```php
// TODO
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.
