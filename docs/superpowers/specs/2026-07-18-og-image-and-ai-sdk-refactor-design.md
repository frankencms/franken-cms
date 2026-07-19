# OG Image & AI SDK Refactor — Design

**Date:** 2026-07-18
**Status:** Approved
**Branches:** `feature/og-image-integration` and `feature/laravel-ai-sdk`, both off `dev`. Nothing merges to `main`.
**BC policy:** None required (beta). Existing behavior may be rewritten as long as general functionality is preserved.

## Problem

Two integrations are hard-coded into FrankenCMS:

1. **OG images** are manual-upload only: a `SpatieMediaLibraryFileUpload` field (`seo-og` collection) in `HasSeoFields`, a site-wide default (`og-default` on `SiteSettingsMedia`), and `SeoService::getOgImage()` resolving post upload → site default → null. No generation.
2. **AI** is hard-wired to Prism: `AiService` calls Prism directly, `AiFeatureDetector` checks for Prism's service provider, `AiModelService` hand-rolls HTTP calls to each provider's model-list API, and `AiSettings` stores the API key in the database (a security concern).

Both become self-contained, optional integration modules following shared conventions (Approach A — no generic driver framework; each integration wraps exactly one implementation, so a Manager/driver layer would be YAGNI).

## Part 1 — OG Image integration (`src/OgImage/`)

### Dependency

- `spatie/laravel-og-image` added to `composer.json` `suggest`, documented as the recommended setup.
- Gate class `FrankenCms\OgImage\OgImageFeature::isAvailable()`: Spatie provider loaded + `config('franken-cms.og_image.enabled')`. Mirrors the `AiFeatureDetector` pattern.
- Rendering backends: local Chrome/Node (Browsershot) **or** Cloudflare Browser Rendering API. Both are configured in the Spatie package's own config — FrankenCMS does not duplicate that config, but the Igor installer publishes it and the docs cover both backends so users never need to read separate documentation.

### Config (`config/franken-cms.php`)

```php
'og_image' => [
    'enabled'   => true,
    'templates' => [
        // post type => Blade view containing <x-og-image>
        'post' => 'theme.og-templates.post',
        'page' => 'theme.og-templates.page',
    ],
],
```

### Rendering model

- Theme authors add `<x-franken-og-image />` to their layout once. The component:
  1. Returns nothing if `OgImageFeature::isAvailable()` is false or the current page has no mapped template.
  2. Otherwise resolves the current post's type via `CurrentPageService`, looks up `og_image.templates`, and renders the mapped view with the current post in scope.
- Mapped templates are ordinary Blade views using Spatie's `<x-og-image>` component (featured image, title, etc. pulled from the post). The example theme ships working `og-templates/post.blade.php` and `og-templates/page.blade.php` plus the layout include.
- Hand-coded (non-CMS) pages use `<x-og-image>` directly per Spatie's docs. No FrankenCMS concepts leak into them.

### Resolution order (`SeoService::getOgImage()`)

1. Mapped template exists for the post type **and** `OgImageFeature::isAvailable()` → Spatie's generated image URL for the current page.
2. Manual per-post upload (`seo-og` collection).
3. Site default (`og-default` on `SiteSettingsMedia`).
4. `null`.

Package not installed / disabled → steps 2–4 only (today's behavior, unchanged).

### Meta tag ownership

`AddSeoDefaults` middleware remains the **single owner** of `og:image` and `twitter:image` meta tags. When generation wins, it points them at the Spatie URL; the Spatie package's own automatic meta injection is not used. No duplicate tags.

### Filament

The SEO tab keeps the manual upload field, with helper text shown when a generated template is active for the post's type (explains the manual upload is a fallback in that case).

### Installer

Igor gains an optional step: offer to `composer require spatie/laravel-og-image`, publish its config, and mention the Cloudflare option for hosts without Chrome/Node.

### Error handling

Generation failures (missing Chrome binary, Cloudflare API errors) log a warning and fall through to the next resolution step. A broken OG setup must never break page rendering.

## Part 2 — AI on the first-party Laravel AI SDK

### Dependency

- Prism (`prism-php/prism`) removed entirely — code references, `suggest` entry, docs.
- `laravel/ai` added to `composer.json` `suggest` (optional, like Prism was).

### Feature detection

`AiFeatureDetector::isAvailable()` = SDK service provider loaded **and** at least one provider has credentials configured (`config/ai.php` / `.env`) **and** `AiSettings->enabled`. A helper exposes the list of configured providers for the settings UI.

### Settings redesign (security fix)

- `AiSettings` **drops** `provider`, `api_key` (and the `EncryptedSettingsCast` usage for it). API keys live only in `.env` per SDK convention.
- Kept: master `enabled` toggle, per-feature toggles + prompts (unchanged), and a `model` selection whose options come only from providers that actually have credentials configured.
- A settings migration removes the stored key/provider values from the database.
- The AI settings tab shows setup guidance (which env vars to set) when no provider is configured.

### Service layer

- `AiService` keeps its current public API — text generation, image/vision generation, streaming — so consumers (`BaseAiAction`, `GenerateAltTextAction`, `GenerateBlogPostAction`, `GenerateImageTitleAction`, `BlogPostWizard`) are untouched apart from import/detection tweaks. Internals swap from Prism calls to SDK calls; SDK streaming feeds the existing Livewire `stream()` in `BlogPostWizard`.
- `AiModelService`: hand-rolled per-provider HTTP fetchers (~300 lines) are replaced with the SDK's model catalog where available, falling back to a small curated per-provider list. Caching of fetched lists may go away if the SDK makes it unnecessary.

## Cross-cutting

- **Testing:** both optional packages become dev dependencies so Pest/Testbench can exercise the installed path. Feature-gate tests cover not-installed and not-configured paths. Existing SEO/AI tests are updated freely (no BC).
- **Docs:** README sections for both integrations, including the recommended-setup framing for OG images and the `.env` key convention for AI.
- **Order of work:** the two branches are independent; either can land first.
