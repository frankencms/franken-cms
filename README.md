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

## AI Content Generation (Igor)

Franken CMS ships with optional AI-assisted content generation (SEO titles, meta descriptions, teasers, alt text, blog post drafts) built on the [laravel/ai](https://github.com/laravel/ai) SDK. It's entirely opt-in — the package is not required to run Franken CMS.

### Install the SDK

```bash
composer require laravel/ai
```

### Configure a provider

Add your API key(s) to `.env`. Franken CMS reads provider credentials straight from `config/ai.php`, so no key is stored in the database:

```env
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
```

See `vendor/laravel/ai/config/ai.php` for the full list of supported providers (Azure, Bedrock, Cohere, DeepSeek, ElevenLabs, Groq, Jina, Mistral, OpenAI-compatible, OpenRouter, VoyageAI, xAI, and more). Optionally publish the config to customize it further:

```bash
php artisan vendor:publish --tag=ai-config
```

Ollama requires no API key. It's disabled by default to avoid advertising a "local" provider that may not actually be running; opt in explicitly in `.env`:

```env
CMS_AI_ENABLE_OLLAMA=true
```

### Enable Igor

With the SDK installed and at least one provider configured, go to **CMS Settings → Igor** in the Filament admin panel and toggle Igor on. AI features activate only when all three are true: the SDK is installed, a provider has credentials, and the toggle is enabled.

> **Note:** API keys are no longer stored in the database. If you're upgrading from an older Franken CMS version, remove any stale `cms_ai.api_key` row from your settings table (or run `php artisan migrate:fresh`) and set the key in `.env` instead.

## Open Graph Images

Franken CMS can generate og:image and twitter:image previews automatically for posts and pages, built on [spatie/laravel-og-image](https://github.com/spatie/laravel-og-image). It's entirely opt-in — without the package, manual per-post uploads and the site default image keep working exactly as before.

### Install the package

```bash
composer require spatie/laravel-og-image
```

The `franken-cms:install` command will also offer to install and wire it up for you.

### Map templates

Templates are mapped by post type in `config/franken-cms.php`:

```php
'og_image' => [
    'templates' => [
        'post' => 'theme.og-templates.post',
        'page' => 'theme.og-templates.page',
    ],
],
```

Each template is a plain Blade view rendered on a 1200×630 canvas and receives `$post` — do not wrap it in `<x-og-image>` yourself, Franken CMS's component handles that.

### Add the component

Drop `<x-franken-og-image />` into your theme layout before `</body>` (the example theme ships with it already in place).

### Resolution order

For each page, an image is resolved in this order: mapped template → per-post uploaded image → site default image. Posts that opt into Twitter summary cards (instead of large-image cards) keep the classic manual `og:image`/`twitter:image` tag path.

### Rendering environment

Image generation needs Chrome and Node available on the server. If that's not an option, set the following in `.env` to render via Cloudflare's Browser Rendering API instead:

```env
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ACCOUNT_ID=
```

### Hand-coded pages

For routes outside the CMS (hand-coded, non-post/page views), use `<x-og-image>` directly per [Spatie's documentation](https://github.com/spatie/laravel-og-image).

FrankenCMS's OG image generation is scoped to CMS-managed pages, so on these non-CMS routes it keeps emitting its own classic tags (including a site default `og:image`, if one is configured). That means a page using `<x-og-image>` directly will end up with Spatie's generated tag alongside FrankenCMS's classic tag. Crawlers generally take the first `og:image` they encounter, so if that matters, either remove the site default or skip the `<x-og-image>` component on that page.

### Previewing

Append `?ogimage` to any page URL to preview the generated image directly in the browser.

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
