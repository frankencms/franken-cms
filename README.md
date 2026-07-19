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

### Text and image engines

Text generation and image generation are configured independently in **CMS Settings → Igor → Provider**:

**Text Generation** — powers SEO titles, meta descriptions, teasers, alt text, and blog post drafts.

- **Provider** — any configured provider.
- **Model** — fetched live from the provider's API; use **Refresh Models** to reload the list after adding a key.

**Image Generation** — powers featured image generation (and any future image features).

- **Provider** — only *image-capable* providers appear (OpenAI, Gemini, Azure, Bedrock, xAI, OpenRouter). Leave it on **Auto** and Franken CMS uses the SDK's `default_for_images` when that provider has credentials, falling back to your first configured image-capable provider otherwise — so a single `OPENAI_API_KEY` works with no selection at all.
- **Model** — a curated list of known image models for the chosen provider; leave empty for the provider's default.
- **Quality** — low / medium / high (applies to generated images).

Both engines have a **Test Model** button that fires a minimal probe with the currently selected provider/model and reports either success or the provider's exact error. Use it whenever a model misbehaves — providers (notably OpenAI) list models in their API that your *project* may not have permission to invoke, and the only reliable way to know is a real call. The image test generates one small low-quality image, so it asks for confirmation first (your provider bills for it, typically a cent or two).

### Featured image generation

Igor can generate a post's featured image using the image engine above. Configure the feature itself in **CMS Settings → Igor → Prompts → Featured Image Generation**:

- A toggle to enable/disable the feature
- A prompt template with `{title}` and `{excerpt}` placeholders (`{excerpt}` is filled from the post's teaser field)

Once enabled, a **Generate with AI** button appears next to the featured image upload when editing an existing post or page (it's hidden while creating a new one, since there's no record yet to attach the image to). The button opens a modal with the prompt pre-filled from your template, editable before generating. The generated image's aspect ratio follows the featured image aspect ratio configured in Media settings (21:9 and custom ratios fall back to 16:9), and it replaces the current featured image on success.

### Troubleshooting

- **"Model test failed" / 403 errors** — your provider project doesn't have access to the selected model, even though it appears in the model list. Pick another model or enable it in your provider's console (for OpenAI: project settings → limits/model access).
- **"The model returned no content"** — usually a reasoning-family model spending its whole token budget thinking. Franken CMS allows generous headroom, but if a model consistently returns nothing, choose a non-reasoning variant for short-form generation.

## Open Graph Images

Franken CMS can generate og:image and twitter:image previews automatically for posts and pages, built on [spatie/laravel-og-image](https://github.com/spatie/laravel-og-image). It's entirely opt-in — without the package, manual per-post uploads and the site default image keep working exactly as before.

### Install the package

```bash
composer require spatie/laravel-og-image
```

The `franken-cms:install` command will also offer to install and wire it up for you.

### Map templates

Templates are mapped by post type in `config/franken-cms.php`. **Publish the config if you haven't** (`php artisan vendor:publish --tag=franken-cms-config`) — the mappings ship commented out, so generation stays off until you enable them:

```php
'og_image' => [
    'templates' => [
        'post' => 'theme.og-templates.post',
        'page' => 'theme.og-templates.page',
    ],
],
```

Each template is a plain Blade view rendered on a 1200×630 canvas and receives `$post` — do not wrap it in `<x-og-image>` yourself, Franken CMS's component handles that.

The example theme ships two ready-made designs in `theme/og-templates/`:

- **`post.blade.php` — the "specimen card":** category + specimen-number chip, gradient display title, author ⌁ date ⌁ read-time metadata, and the featured image seamed in with a lightning-bolt edge and suture stitches.
- **`page.blade.php` — the "dossier card":** the calmer sibling — page-path chip, site tagline, and a gradient bolt watermark when the page has no featured image.

Both are fully self-contained (fonts and CSS live inside the template, no asset rebuild needed), scale the title to its length, honor each image's focal point, and degrade gracefully when data is missing — edit them freely, or use them as a reference for your own. Preview any page's card by appending `?ogimage` to its URL; the image URL contains a content hash, so design changes bust caches automatically.

### Add the component

Drop `<x-franken-og-image />` into your theme layout before `</body>` (the example theme ships with it already in place).

### Resolution order

For each page, an image is resolved in this order: mapped template → per-post uploaded image → site default image → site-wide fallback template. The fallback template is opt-in — point `og_image.default_template` in `config/franken-cms.php` at a Blade view (the example theme ships one at `theme.og-templates.default`) and it generates an image for any page that would otherwise have none. Posts that opt into Twitter summary cards (instead of large-image cards) keep the classic manual `og:image`/`twitter:image` tag path.

### Rendering environment

Image generation needs Chrome and Node available on the server:

```bash
npm install puppeteer
npx puppeteer browsers install chrome
```

If that's not an option, set the following in `.env` to render via Cloudflare's Browser Rendering API instead:

```env
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ACCOUNT_ID=
```

> **Cloudflare needs a publicly reachable URL.** Its browser runs in Cloudflare's cloud and fetches your page to screenshot it, so it cannot see local domains like `https://myapp.test` — screenshots fail with a network error. Use the local Chrome driver for local development (leave the `CLOUDFLARE_*` vars unset there) and enable Cloudflare in deployed environments.

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
