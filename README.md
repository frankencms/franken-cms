# Franken CMS is a Wordpress alternative powered by Laravel and FilamentPHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/frankencms/franken-cms.svg?style=flat-square)](https://packagist.org/packages/frankencms/franken-cms)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/frankencms/franken-cms/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/frankencms/franken-cms/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/frankencms/franken-cms/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/frankencms/franken-cms/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/frankencms/franken-cms.svg?style=flat-square)](https://packagist.org/packages/frankencms/franken-cms)

Breathing life into modern content management! ⚡ Franken CMS is a Laravel alternative that gives you the freedom to
build your app your way. Powered by FilamentPHP and the rest of the TALL stack (Tailwind CSS, Alpine.js, and Livewire),
Franken CMS provides a powerful foundation to jumpstart your project—without forcing you into a rigid structure. Stitch
together your perfect app and spark something extraordinary with Franken CMS! ⚡

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Features](#features)
  - [Dynamic Settings System](#dynamic-settings-system)
  - [Content Management](#content-management)
  - [Extensible Architecture](#extensible-architecture)
- [Usage](#usage)
- [Documentation](#documentation)
- [Testing](#testing)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [Security](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

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

## Features

### Dynamic Settings System

Franken CMS features a powerful dynamic settings system that allows both the core CMS and external packages to register their own settings tabs. This modular approach ensures extensibility without conflicts.

- **Modular Settings**: Each feature area has its own settings class and database group
- **External Package Support**: Third-party packages can easily add their own settings tabs
- **Type-Safe**: Full support for typed settings classes using spatie/laravel-settings
- **Conflict Prevention**: Namespaced groups prevent naming conflicts between packages

[📖 Dynamic Settings Documentation](docs/DYNAMIC_SETTINGS_TABS.md)

### Content Management

- **Posts & Pages**: Full content management with custom post types
- **Taxonomies**: Categories and tags with custom taxonomy support
- **User Roles**: WordPress-style user roles and permissions
- **Media Management**: Image handling with multiple size variants

### Extensible Architecture

- **Plugin System**: Easy integration for external packages
- **Custom Fields**: Extensible field system for additional content
- **Theme Support**: Flexible theming system
- **FilamentPHP Integration**: Leverages FilamentPHP's powerful admin interface

## Usage

### Basic Setup

```php
// Access CMS settings
$generalSettings = app(\FrankenCms\Settings\GeneralSettings::class);
echo $generalSettings->title; // Site title

// Get current page service
$currentPage = app(\FrankenCms\Services\CurrentPageService::class);
$page = $currentPage->getCurrentPage();
```

### Adding Custom Settings Tab

```php
// In your package's service provider
public function boot(): void
{
    $settingsTabService = $this->app->make(\FrankenCms\Services\SettingsTabService::class);
    $settingsTabService->registerTab(new YourPackageSettingsTabProvider());
}
```

## Documentation

### 🚀 Getting Started
- [Dynamic Settings Guide](docs/DYNAMIC_SETTINGS_GUIDE.md) - **Start here!** User-friendly guide with examples
- [Installation Guide](docs/INSTALLATION.md) - Complete setup instructions

### 🔧 Development
- [Dynamic Settings Technical Reference](docs/DYNAMIC_SETTINGS_TABS.md) - Complete technical documentation
- [Content Management](docs/CONTENT_MANAGEMENT.md) - Working with posts, pages, and taxonomies
- [Extending Franken CMS](docs/EXTENDING.md) - How to create packages and extensions
- [API Reference](docs/API.md) - Complete API documentation

### 📚 More Resources
- [Examples](examples/) - Real-world code examples
- [Changelog](CHANGELOG.md) - Version history and updates

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
