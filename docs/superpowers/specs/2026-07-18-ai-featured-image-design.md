# AI Featured Image Generation — Design

**Date:** 2026-07-18
**Status:** Approved
**Branch:** `feature/ai-featured-image` off `dev`. Nothing merges to `main`.
**BC policy:** None required (beta).

## Problem

FrankenCMS's AI features (via the first-party `laravel/ai` SDK) generate text only. Editors should optionally be able to generate a post's featured image with an image-capable model, steered per-post, following the established AI feature patterns (`AiFeatureDetector` gating, per-feature toggle + prompt in `AiSettings`, Filament action UX).

## Decisions (user-approved)

- **UX:** a "Generate with AI" action next to the featured upload opens a modal with an editable prompt textarea, pre-filled from the admin-configured template interpolated with the post's title/excerpt (plain string interpolation — no AI call to open the modal). Generated image replaces the featured image on success.
- **Model selection:** the SDK's `default_for_images` (from `config/ai.php`) by default, with optional admin override (provider + model selects in the AI settings tab, populated only with image-capable configured providers).
- **Aspect ratio:** derived from the existing `MediaSettings` featured aspect ratio, mapped to the SDK's `size()` aspect strings (e.g. `16:9`). No new ratio setting.
- **V1 constraint:** the action shows only when a record exists (edit pages); hidden on create. Applies to Pages automatically via the shared form schema.

## Components

### AiImageService (`src/Services/AiImageService.php`)

- `generate(string $prompt): GeneratedImage` — calls `Laravel\Ai\Image::of($prompt)` with:
  - `size()` mapped from `MediaSettings` featured aspect ratio (nearest supported aspect string; sensible default `16:9` when custom/unmappable),
  - `quality()` from `AiSettings->featured_image_quality`,
  - provider/model override from settings when set, else SDK `default_for_images`.
- Throws on unavailability (mirrors `AiService::generate()` guard style, including validating any settings-selected image provider is still configured).

### AiFeatureDetector additions

- `imageCapableProviders(): array` — `configuredProviders()` intersected with providers whose SDK gateway supports image generation. The capable-provider list is derived from vendor source at implementation time (which gateways implement the SDK's image generation), not guessed.
- Image feature available = `isAvailable()` + `featured_image_enabled` + ≥1 image-capable provider.

### AiSettings additions (AI tab, existing per-feature pattern)

- `featured_image_enabled` (bool, default true — feature still requires an image-capable provider to light up)
- `featured_image_prompt` (string template; default e.g. "Editorial blog header illustration, no embedded text, about: {title}")
- `featured_image_quality` (`low|medium|high`, default `medium`)
- `featured_image_provider` / `featured_image_model` (nullable; empty = SDK default). Provider options come from `imageCapableProviders()`.
- Settings migration adds the new properties to the `franken_cms_ai` group.

### GenerateFeaturedImageAction (`src/Filament/Actions/`)

- Standalone Filament `Action` (not a `BaseAiAction` subclass — that base is text-generation-oriented).
- Placed next to `featured_image` upload in `PostForm`; visible only when the image feature is available AND the record exists.
- Modal: single textarea `prompt`, pre-filled from the settings template with `{title}` / `{excerpt}` interpolated from the current form state.
- On submit: `AiImageService::generate($prompt)` → `GeneratedImage::store()` to a temp location → `$record->addMedia($path)->toMediaCollection('featured')` (single-file collection semantics replace the previous image) → refresh the upload field state → success notification.
- On failure: Filament danger notification with the error message; existing featured image untouched.

## Error handling

- No image-capable provider / feature disabled → action hidden (never a dead button).
- Generation/API errors → caught, surfaced as a notification, no media mutation.
- Temp file cleanup after successful media attachment.

## Testing

- `AiImageService`: use the SDK's image-generation fake if the vendor ships one (verify at plan time); otherwise mock at the gateway/provider seam. Assert size/quality/provider mapping and the settings-override path.
- `AiFeatureDetector::imageCapableProviders()`: config-driven unit tests.
- Action: feature test asserting media attach + replace semantics on a real record, and hidden-when-unavailable.
- Settings migration test coverage consistent with neighboring migrations.

## Docs

README AI section gains a short "Featured image generation" subsection (requirements: image-capable provider key, e.g. `OPENAI_API_KEY`; where the toggle/prompt live).
