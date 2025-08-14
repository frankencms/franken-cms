# Franken CMS is a Wordpress alternative powered by Laravel and FilamentPHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/frankencms/franken-cms.svg?style=flat-square)](https://packagist.org/packages/frankencms/franken-cms)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/frankencms/franken-cms/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/frankencms/franken-cms/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/frankencms/franken-cms/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/frankencms/franken-cms/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/frankencms/franken-cms.svg?style=flat-square)](https://packagist.org/packages/frankencms/franken-cms)

Breathing life into modern content management! ⚡ Franken CMS is a Laravel alternative that gives you the freedom to
build your app your way. Powered by FilamentPHP and the rest of the TALL stack (Tailwind CSS, Alpine.js, and Livewire),
Franken CMS provides a powerful foundation to jumpstart your project—without forcing you into a rigid structure. Stitch
together your perfect app and spark something extraordinary with Franken CMS! ⚡

## Prerequisites

Franken CMS uses FilamantPHP for its interface and panels. Make sure you have FilamentPHP installed before installing
Franken CMS in your Laravel project. You can find the installation instructions for
FilamentPHP [here](https://filamentphp.com/docs/4.x/panels/installation)


## Installation

You can install the package via composer:

```bash
composer require frankencms/franken-cms
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="franken-cms-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="franken-cms-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="franken-cms-views"
```

## Usage

```php
$frankenCms = new Franken CMS\FrankenCms();
echo $frankenCms->echoPhrase('Hello, Franken CMS!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/frankencms)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
