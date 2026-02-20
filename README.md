# Franken CMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/frankencms/franken-cms.svg?style=flat-square)](https://packagist.org/packages/frankencms/franken-cms)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/frankencms/franken-cms/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/frankencms/franken-cms/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/frankencms/franken-cms.svg?style=flat-square)](https://packagist.org/packages/frankencms/franken-cms)

A WordPress alternative for Laravel. Powered by FilamentPHP and the TALL stack.

Franken CMS gives you content management — posts, pages, taxonomies, menus, SEO, and a template field system — as a Laravel package. Build your app your way without being locked into a rigid structure.

> **Beta** — Franken CMS is in active development. APIs may change before the stable release.

## Documentation

Full documentation is available at **[frankencms.com](https://frankencms.com)**.

## Quick Start

### Requirements

- PHP 8.2+
- Laravel 11+
- FilamentPHP v5 ([installation guide](https://filamentphp.com/docs))

### Installation

```bash
composer require frankencms/franken-cms
```

Then run the installer:

```bash
php artisan franken-cms:install
```

The installer will guide you through publishing config, running migrations, registering the Filament plugin, and setting up your theme.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for version history.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/frankencms)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
