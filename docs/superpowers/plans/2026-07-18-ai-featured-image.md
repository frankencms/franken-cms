# AI Featured Image Generation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let editors generate a post's featured image with an image-capable laravel/ai model via a prompt modal next to the featured upload.

**Architecture:** `AiImageService` wraps `Laravel\Ai\Image` (aspect from MediaSettings, quality/provider/model from AiSettings). `AiFeatureDetector` gains `imageCapableProviders()`/`isImageAvailable()`. A standalone `GenerateFeaturedImageAction` (modal with editable prompt pre-filled from a settings template) attaches the stored image to the `featured` media collection on edit pages.

**Tech Stack:** PHP 8.4, Laravel 13, `laravel/ai` 0.9 (dev dependency; `Image::fake()` for tests), FilamentPHP v5, Pest v4, PHPStan (`--memory-limit=1G`), Pint.

## Global Constraints

- Branch: `feature/ai-featured-image` off `dev`. Never push to `main`. No BC required (beta).
- Spec: `docs/superpowers/specs/2026-07-18-ai-featured-image-design.md`.
- Image-capable providers (verified from vendor — classes implementing `Laravel\Ai\Contracts\Providers\ImageProvider`): `openai`, `gemini`, `azure`, `bedrock`, `xai`, `openrouter`.
- Verified SDK API: `Image::of(string $prompt): PendingImageGeneration` with `->size(string)` / `->landscape()` / `->quality('low'|'medium'|'high')` / `->generate(Lab|array|string|null $provider = null, ?string $model = null): ImageResponse`; `ImageResponse::firstImage(): GeneratedImage`; `ImageResponse::storeAs(string $path, ?string $name = null, ?string $disk = null): string|bool`; testing via `Image::fake()` + `Image::assertGenerated(Closure)` / `assertNothingGenerated()`.
- V1 constraint: the action is visible only when a record exists (edit pages) AND the image feature is available. Never a dead button.
- `MediaSettings->featured_aspect_ratio` values: `16:9 | 4:3 | 1:1 | 3:2 | 21:9 | custom`. Mapping to SDK size: pass the value through for `16:9`/`4:3`/`1:1`/`3:2`; `21:9` and `custom` map to `16:9` (nearest supported / spec default).
- Settings group is `franken_cms_ai`; next migration number is `23`.
- Run tests: `vendor/bin/pest` (targeted), `composer test` (full). Analysis: `vendor/bin/phpstan analyse --memory-limit=1G` (~235 pre-existing errors elsewhere are out of scope). Format: `composer format`.
- Pint: aligned `=>`, `new Foo` not `new Foo()`, no Yoda.

---

### Task 1: Branch + AiFeatureDetector image methods

**Files:**
- Modify: `src/Services/AiFeatureDetector.php`
- Test: `tests/Unit/AiFeatureDetectorTest.php` (append)

**Interfaces:**
- Produces: `AiFeatureDetector::imageCapableProviders(): array` (provider-name => label, subset of `configuredProviders()`), `AiFeatureDetector::isImageAvailable(): bool` (= `isAvailable()` && `AiSettings->featured_image_enabled` && ≥1 image-capable provider). NOTE: `featured_image_enabled` doesn't exist until Task 2 — in this task, read it defensively: `(bool) (app(AiSettings::class)->featured_image_enabled ?? true)` will throw on a missing typed property, so instead gate only on `isAvailable()` + providers here and let Task 2 add the settings check (see Step 3 code — the property read is added in Task 2, tracked by a test added in Task 2).

- [ ] **Step 1: Create the branch**

```bash
git checkout dev && git pull --ff-only && git checkout -b feature/ai-featured-image
```

- [ ] **Step 2: Write the failing tests**

Append to `tests/Unit/AiFeatureDetectorTest.php`:

```php
describe('imageCapableProviders', function () {
    test('returns only configured providers that support image generation', function () {
        config()->set('ai.providers', [
            'openai'    => ['driver' => 'openai', 'key' => 'sk-test'],
            'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
            'gemini'    => ['driver' => 'gemini', 'key' => null],
        ]);

        // anthropic is configured but not image-capable; gemini is capable but unconfigured
        expect(AiFeatureDetector::imageCapableProviders())->toBe(['openai' => 'Openai']);
    });

    test('returns empty array when nothing image-capable is configured', function () {
        config()->set('ai.providers', [
            'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
        ]);

        expect(AiFeatureDetector::imageCapableProviders())->toBe([]);
    });
});

describe('isImageAvailable', function () {
    test('is false when no image-capable provider is configured', function () {
        config()->set('ai.providers', ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        expect(AiFeatureDetector::isImageAvailable())->toBeFalse();
    });

    test('is true when AI is enabled and an image-capable provider is configured', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        expect(AiFeatureDetector::isImageAvailable())->toBeTrue();
    });
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/AiFeatureDetectorTest.php`
Expected: FAIL — methods undefined.

- [ ] **Step 4: Implement**

Add to `src/Services/AiFeatureDetector.php`:

```php
/**
 * Providers whose laravel/ai gateway supports image generation
 * (classes implementing Laravel\Ai\Contracts\Providers\ImageProvider)
 */
protected const IMAGE_CAPABLE_DRIVERS = ['openai', 'gemini', 'azure', 'bedrock', 'xai', 'openrouter'];

/**
 * Configured providers that support image generation
 *
 * @return array<string, string> provider name => display label
 */
public static function imageCapableProviders(): array
{
    return collect(self::configuredProviders())
        ->filter(function (string $label, string $name) {
            $driver = config("ai.providers.{$name}.driver", $name);

            return in_array($driver, self::IMAGE_CAPABLE_DRIVERS, true);
        })
        ->all();
}

/**
 * Check if featured image generation is available
 */
public static function isImageAvailable(): bool
{
    return self::isAvailable() && ! empty(self::imageCapableProviders());
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/AiFeatureDetectorTest.php`
Expected: PASS (all, including pre-existing).

- [ ] **Step 6: Commit**

```bash
git add src/Services/AiFeatureDetector.php tests/Unit/AiFeatureDetectorTest.php
git commit -m "feat: detect image-capable AI providers"
```

---

### Task 2: AiSettings image properties + migration + settings tab

**Files:**
- Modify: `src/Settings/AiSettings.php`
- Create: `database/migrations/23_add_ai_featured_image_settings.php`
- Modify: `src/SettingsTabs/AiSettingsTabProvider.php`
- Modify: `src/Services/AiFeatureDetector.php` (fold the toggle into `isImageAvailable()`)
- Test: `tests/Unit/AiFeatureDetectorTest.php` (append)

**Interfaces:**
- Produces on `AiSettings`: `bool $featured_image_enabled = true`, `string $featured_image_prompt = ''`, `string $featured_image_quality = 'medium'`, `?string $featured_image_provider = null`, `?string $featured_image_model = null`.
- `isImageAvailable()` becomes: `isAvailable()` && `featured_image_enabled` && image-capable provider exists.

- [ ] **Step 1: Add settings properties**

In `src/Settings/AiSettings.php`, after the existing per-feature blocks (follow the `// Image Alt Text Prompt` block style):

```php
// Featured Image Generation
public bool $featured_image_enabled = true;

public string $featured_image_prompt = '';

public string $featured_image_quality = 'medium';

public ?string $featured_image_provider = null;

public ?string $featured_image_model = null;
```

- [ ] **Step 2: Create the settings migration**

`database/migrations/23_add_ai_featured_image_settings.php` (match the base-class conventions of `22_remove_ai_api_key_setting.php`):

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('franken_cms_ai.featured_image_enabled', true);
        $this->migrator->add('franken_cms_ai.featured_image_prompt', 'Editorial blog header illustration, modern and clean, no embedded text, about: {title}');
        $this->migrator->add('franken_cms_ai.featured_image_quality', 'medium');
        $this->migrator->add('franken_cms_ai.featured_image_provider', null);
        $this->migrator->add('franken_cms_ai.featured_image_model', null);
    }
};
```

Also add the same default prompt string to the `19_create_ai_settings.php` create-migration IF that file seeds per-feature prompts (read it first; mirror however `alt_text_prompt` is seeded — if defaults live only in the class, do nothing there). The class default for `featured_image_prompt` stays `''` with the effective default coming from the migration, matching whichever pattern `19` uses for the other prompts.

- [ ] **Step 3: Fold the toggle into isImageAvailable + test**

Append to the `isImageAvailable` describe block in `tests/Unit/AiFeatureDetectorTest.php`:

```php
test('is false when the featured image feature is disabled in settings', function () {
    config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
    $settings = app(AiSettings::class);
    $settings->enabled = true;
    $settings->featured_image_enabled = false;
    $settings->save();

    expect(AiFeatureDetector::isImageAvailable())->toBeFalse();
});
```

Update `isImageAvailable()` in `src/Services/AiFeatureDetector.php`:

```php
public static function isImageAvailable(): bool
{
    if (! self::isAvailable()) {
        return false;
    }

    if (! app(AiSettings::class)->featured_image_enabled) {
        return false;
    }

    return ! empty(self::imageCapableProviders());
}
```

- [ ] **Step 4: Settings tab section**

In `src/SettingsTabs/AiSettingsTabProvider.php`, add a "Featured Image Generation" section following the existing per-feature section pattern (read one, e.g. the alt-text section, and mirror its Section/Toggle/Textarea structure):

- `Toggle::make('featured_image_enabled')` — label "Featured Image Generation", helper "Generate featured images with an image-capable AI model".
- `Textarea::make('featured_image_prompt')` — label "Image Prompt Template", helper "Pre-fills the generation prompt. Placeholders: {title}, {excerpt}", visible when enabled.
- `Select::make('featured_image_quality')` — options `['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']`, default `medium`, visible when enabled.
- `Select::make('featured_image_provider')` — label "Image Provider", options `AiFeatureDetector::imageCapableProviders()`, placeholder "SDK default (config/ai.php)", nullable, `->live()`, visible when enabled.
- `TextInput::make('featured_image_model')` — label "Image Model", helper "Leave empty for the provider default", visible when enabled and a provider is selected.
- The whole section gets a guidance `TextEntry`/placeholder visible when `empty(AiFeatureDetector::imageCapableProviders())`: "No image-capable provider configured. Add e.g. OPENAI_API_KEY or GEMINI_API_KEY to your .env."

- [ ] **Step 5: Run the suite**

Run: `vendor/bin/pest tests/Unit/AiFeatureDetectorTest.php` then `composer test`
Expected: PASS, no regressions (settings migration runs in Testbench automatically like its neighbors).

- [ ] **Step 6: Commit**

```bash
git add src/Settings/AiSettings.php database/migrations/23_add_ai_featured_image_settings.php src/SettingsTabs/AiSettingsTabProvider.php src/Services/AiFeatureDetector.php tests/Unit/AiFeatureDetectorTest.php
git commit -m "feat: add featured image generation settings"
```

---

### Task 3: AiImageService

**Files:**
- Create: `src/Services/AiImageService.php`
- Test: `tests/Unit/AiImageServiceTest.php`

**Interfaces:**
- Consumes: `AiFeatureDetector::isImageAvailable()/imageCapableProviders()`, `AiSettings` image props (Task 2), `MediaSettings->featured_aspect_ratio`.
- Produces: `AiImageService::generate(string $prompt): ImageResponse` (throws `Exception` when unavailable or when the settings-selected provider is no longer configured) and `AiImageService::aspectSize(): string`. Register as singleton alongside the other AI singletons in `FrankenCmsServiceProvider` (inside the existing `AiFeatureDetector::isInstalled()` gate).

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/AiImageServiceTest.php`:

```php
<?php

use FrankenCms\Services\AiImageService;
use FrankenCms\Settings\AiSettings;
use FrankenCms\Settings\MediaSettings;
use Laravel\Ai\Image;

beforeEach(function () {
    config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
    $settings = app(AiSettings::class);
    $settings->enabled = true;
    $settings->featured_image_enabled = true;
    $settings->save();

    $this->service = new AiImageService;
});

describe('generate', function () {
    test('throws when image generation is not available', function () {
        config()->set('ai.providers', []);

        $this->service->generate('a mountain');
    })->throws(Exception::class, 'not available');

    test('generates with the mapped aspect ratio and configured quality', function () {
        Image::fake();

        $media = app(MediaSettings::class);
        $media->featured_aspect_ratio = '4:3';
        $media->save();

        $settings = app(AiSettings::class);
        $settings->featured_image_quality = 'high';
        $settings->save();

        $this->service->generate('a mountain');

        Image::assertGenerated(fn ($prompt) => str_contains((string) $prompt, 'a mountain'));
    });

    test('throws when the settings-selected image provider is no longer configured', function () {
        $settings = app(AiSettings::class);
        $settings->featured_image_provider = 'gemini'; // capable but unconfigured
        $settings->save();

        $this->service->generate('a mountain');
    })->throws(Exception::class, 'not configured');
});

describe('aspectSize', function () {
    test('passes through supported ratios and maps the rest to 16:9', function () {
        $media = app(MediaSettings::class);

        foreach (['16:9' => '16:9', '4:3' => '4:3', '1:1' => '1:1', '3:2' => '3:2', '21:9' => '16:9', 'custom' => '16:9'] as $configured => $expected) {
            $media->featured_aspect_ratio = $configured;
            $media->save();

            expect($this->service->aspectSize())->toBe($expected);
        }
    });
});
```

Note for the implementer: `Image::assertGenerated`'s closure signature should be confirmed against `vendor/laravel/ai/src/Gateway/FakeImageGateway.php` (it records `ImagePrompt` objects — adjust the closure to whatever the recorded object exposes, e.g. `$prompt->prompt`). Pin real fields; don't weaken to `assertGenerated(fn () => true)` — asserting on the prompt text is the point. If the fake records size/quality too, extend the assertion to pin `4:3` and `high`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/AiImageServiceTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement**

Create `src/Services/AiImageService.php`:

```php
<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Settings\AiSettings;
use FrankenCms\Settings\MediaSettings;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

class AiImageService
{
    /**
     * Aspect ratios the SDK accepts directly; everything else maps to 16:9
     */
    protected const SUPPORTED_ASPECTS = ['16:9', '4:3', '1:1', '3:2'];

    /**
     * Generate a featured image from a prompt
     *
     * @throws Exception
     */
    public function generate(string $prompt): ImageResponse
    {
        if (! AiFeatureDetector::isImageAvailable()) {
            throw new Exception('AI image generation is not available. Configure an image-capable provider (e.g. OPENAI_API_KEY) and enable it in settings.');
        }

        $settings = app(AiSettings::class);

        $provider = $settings->featured_image_provider;

        if ($provider && ! array_key_exists($provider, AiFeatureDetector::imageCapableProviders())) {
            throw new Exception("The selected image provider [{$provider}] is not configured. Update the AI settings or set its API key in your .env.");
        }

        try {
            return Image::of($prompt)
                ->size($this->aspectSize())
                ->quality($settings->featured_image_quality)
                ->generate(
                    provider: $provider,
                    model: $provider ? $settings->featured_image_model : null,
                );
        } catch (Exception $e) {
            throw new Exception('AI image generation failed: ' . $e->getMessage());
        }
    }

    /**
     * The SDK size string matching the configured featured aspect ratio
     */
    public function aspectSize(): string
    {
        $ratio = app(MediaSettings::class)->featured_aspect_ratio;

        return in_array($ratio, self::SUPPORTED_ASPECTS, true) ? $ratio : '16:9';
    }
}
```

Register the singleton in `src/FrankenCmsServiceProvider.php` inside the existing `if (AiFeatureDetector::isInstalled())` block (next to `AiService`):

```php
$this->app->singleton(AiImageService::class);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/AiImageServiceTest.php`
Expected: PASS. Re-throw wrapping: the "not configured" exception must NOT be double-wrapped by the catch — it's thrown before the try block; keep it that way.

- [ ] **Step 5: Commit**

```bash
git add src/Services/AiImageService.php src/FrankenCmsServiceProvider.php tests/Unit/AiImageServiceTest.php
git commit -m "feat: add AiImageService wrapping laravel/ai image generation"
```

---

### Task 4: GenerateFeaturedImageAction + PostForm wiring

**Files:**
- Create: `src/Filament/Actions/GenerateFeaturedImageAction.php`
- Modify: `src/Filament/Resources/Post/Schemas/PostForm.php` (featured upload, ~line 342 — add `->hintAction(...)`)
- Test: `tests/Feature/GenerateFeaturedImageActionTest.php`

**Interfaces:**
- Consumes: `AiImageService::generate(string): ImageResponse` (Task 3), `AiFeatureDetector::isImageAvailable()` (Task 2), `AiSettings->featured_image_prompt`.
- Produces: `GenerateFeaturedImageAction::make(string $name)` — a Filament `Action` attachable via `->hintAction(...)`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/GenerateFeaturedImageActionTest.php`. Test the pieces that don't need a full Livewire page run (the modal wiring is thin; the attach logic is the risk):

```php
<?php

use FrankenCms\Filament\Actions\GenerateFeaturedImageAction;
use FrankenCms\Models\Post;
use FrankenCms\Settings\AiSettings;
use Laravel\Ai\Image;

beforeEach(function () {
    config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
    $settings = app(AiSettings::class);
    $settings->enabled = true;
    $settings->featured_image_enabled = true;
    $settings->featured_image_prompt = 'Header about: {title}';
    $settings->save();
});

test('fills the prompt template from post data', function () {
    $prompt = GenerateFeaturedImageAction::fillPromptTemplate('Header about: {title}, focus {excerpt}', [
        'title'   => 'My Post',
        'excerpt' => 'A short teaser',
    ]);

    expect($prompt)->toBe('Header about: My Post, focus A short teaser');
});

test('unknown placeholders are stripped', function () {
    $prompt = GenerateFeaturedImageAction::fillPromptTemplate('About: {title} {nonsense}', ['title' => 'X']);

    expect($prompt)->toBe('About: X');
});

test('generates and attaches the image to the featured collection', function () {
    Image::fake();

    $post = Post::factory()->create();

    GenerateFeaturedImageAction::generateAndAttach($post, 'a mountain at dusk');

    expect($post->refresh()->hasMedia('featured'))->toBeTrue();
    Image::assertGenerated(fn ($prompt) => str_contains((string) $prompt, 'a mountain at dusk'));
});

test('replaces an existing featured image', function () {
    Image::fake();

    $post = Post::factory()->create();
    GenerateFeaturedImageAction::generateAndAttach($post, 'first image');
    GenerateFeaturedImageAction::generateAndAttach($post, 'second image');

    expect($post->refresh()->getMedia('featured'))->toHaveCount(1);
});
```

Note: `Image::fake()` returns base64 image data on `GeneratedImage->image` (confirm the fake's default response shape in `vendor/laravel/ai/src/Gateway/FakeImageGateway.php`; if the default fake response has no storable bytes, pass a fake response with a tiny valid PNG base64 string into `Image::fake([...])` — construct it with `base64_encode(file_get_contents(<existing tiny fixture>))` or a hardcoded 1x1 PNG base64 constant in the test).

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/GenerateFeaturedImageActionTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement the action**

Create `src/Filament/Actions/GenerateFeaturedImageAction.php`:

```php
<?php

namespace FrankenCms\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use FrankenCms\Models\Post;
use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Services\AiImageService;
use FrankenCms\Settings\AiSettings;
use Illuminate\Support\Facades\Storage;

class GenerateFeaturedImageAction
{
    public static function make(string $name): Action
    {
        return Action::make($name)
            ->label('Generate with AI')
            ->icon('heroicon-o-sparkles')
            ->visible(function ($livewire) {
                if (! AiFeatureDetector::isImageAvailable()) {
                    return false;
                }

                return method_exists($livewire, 'getRecord') && $livewire->getRecord() !== null;
            })
            ->form([
                Textarea::make('prompt')
                    ->label('Image prompt')
                    ->rows(4)
                    ->required()
                    ->default(function ($livewire) {
                        $data = $livewire->data ?? [];

                        return self::fillPromptTemplate(app(AiSettings::class)->featured_image_prompt, [
                            'title'   => $data['post_title'] ?? $data['title'] ?? '',
                            'excerpt' => $data['post_excerpt'] ?? $data['excerpt'] ?? '',
                        ]);
                    }),
            ])
            ->modalHeading('Generate Featured Image')
            ->modalSubmitActionLabel('Generate')
            ->action(function (array $data, $livewire) {
                $record = $livewire->getRecord();

                try {
                    self::generateAndAttach($record, $data['prompt']);

                    Notification::make()
                        ->title('Featured image generated')
                        ->success()
                        ->send();

                    // Refresh the form's media state so the new image shows immediately
                    $livewire->refreshFormData(['featured_image']);
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Image generation failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Interpolate {placeholders} into the prompt template; unknown placeholders are stripped
     */
    public static function fillPromptTemplate(string $template, array $context): string
    {
        foreach ($context as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return trim(preg_replace('/\{[a-z_]+\}/', '', $template));
    }

    /**
     * Generate an image and attach it to the post's featured collection
     *
     * @throws Exception
     */
    public static function generateAndAttach(Post $record, string $prompt): void
    {
        $response = app(AiImageService::class)->generate($prompt);

        $generated = $response->firstImage();
        $extension = explode('/', $generated->mime())[1] ?? 'png';
        $tempPath = 'ai-featured/' . uniqid('featured_', true) . '.' . $extension;

        Storage::disk('local')->put($tempPath, base64_decode($generated->image));

        try {
            $record->addMedia(Storage::disk('local')->path($tempPath))
                ->toMediaCollection('featured');
        } finally {
            Storage::disk('local')->delete($tempPath);
        }
    }
}
```

Implementation checks for the engineer: (a) confirm the featured collection is `singleFile()` on the `Post` model (`src/Models/Post.php`, `addMediaCollection('featured')`) — if it is NOT, call `$record->clearMediaCollection('featured')` before `addMedia` so the replace test passes; (b) `refreshFormData` exists on Filament edit pages — verify against how other actions refresh state, and if unavailable on this Livewire component, drop that line and note it (the image still attaches; the upload preview updates on save/reload); (c) `$generated->image` may be a URL for some providers (`$generated->url`) — prefer `ImageResponse::storeAs(...)` if it handles both transparently (read `vendor/laravel/ai/src/Responses/ImageResponse.php` and `GeneratedImage::store()`; if `store()` handles url-or-base64, use `$generated->store($tempPath, 'local')` instead of manual base64 decode).

- [ ] **Step 4: Wire into PostForm**

In `src/Filament/Resources/Post/Schemas/PostForm.php`, on the `SpatieMediaLibraryFileUpload::make('featured_image')` field (~line 342), add alongside the existing chain (mirroring line 444's `hintAction` style):

```php
->hintAction(GenerateFeaturedImageAction::make('generate_featured_image'))
```

with the import `use FrankenCms\Filament\Actions\GenerateFeaturedImageAction;` added to the existing action imports (~line 24).

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/GenerateFeaturedImageActionTest.php` then `composer test`
Expected: PASS, no regressions.

- [ ] **Step 6: Commit**

```bash
git add src/Filament/Actions/GenerateFeaturedImageAction.php src/Filament/Resources/Post/Schemas/PostForm.php tests/Feature/GenerateFeaturedImageActionTest.php
git commit -m "feat: add Generate with AI action for featured images"
```

---

### Task 5: Docs, analysis, push

**Files:**
- Modify: `README.md` (AI section)

- [ ] **Step 1: README subsection**

Under the "AI Content Generation (Igor)" section, add a "Featured image generation" subsection: requires an image-capable provider key (`OPENAI_API_KEY`, `GEMINI_API_KEY`, xAI/Azure/Bedrock/OpenRouter also work); toggle + prompt template (+ optional provider/model override and quality) live in CMS Settings → Igor; the "Generate with AI" button appears next to the featured image upload when editing a post/page; the image model defaults to `config/ai.php`'s `default_for_images`.

- [ ] **Step 2: Analysis + format + suite**

Run: `vendor/bin/phpstan analyse --memory-limit=1G src/Services/AiImageService.php src/Services/AiFeatureDetector.php src/Filament/Actions/GenerateFeaturedImageAction.php src/Settings/AiSettings.php src/SettingsTabs/AiSettingsTabProvider.php` — zero NEW errors (pre-existing baseline elsewhere out of scope).
Run: `composer format` then `composer test` — all green.

- [ ] **Step 3: Commit and push**

```bash
git add -A
git commit -m "docs: document AI featured image generation"
git push -u origin feature/ai-featured-image
```

- [ ] **Step 4: Report**

Note for the user: end-to-end smoke test needs the test app with `laravel/ai` installed and a real image-capable key (e.g. `OPENAI_API_KEY`); generation costs per image apply. Do not modify the test app unasked.
