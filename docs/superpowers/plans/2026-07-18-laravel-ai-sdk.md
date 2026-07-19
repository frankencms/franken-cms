# Laravel AI SDK Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace Prism with the first-party `laravel/ai` SDK; API keys move from database settings to `.env` per SDK convention.

**Architecture:** `AiService` keeps its public API (`generate`, streaming callback, `testConnection`) so Filament actions and `BlogPostWizard` are untouched. A new `CmsAgent` class (implements the SDK `Agent` contract with the `Promptable` trait) carries per-call instructions/max-tokens/temperature. `AiFeatureDetector` gates on: SDK installed + ≥1 provider configured in `config/ai.php` + `AiSettings->enabled`.

**Tech Stack:** PHP 8.4, Laravel 13 (Orchestra Testbench), `laravel/ai` 0.x, Pest v4, PHPStan level 5, Pint.

## Global Constraints

- Branch: `feature/laravel-ai-sdk` off `dev`. Never push to `main`.
- No BC required (beta): migrations may be edited in place; tests rewritten freely.
- Spec: `docs/superpowers/specs/2026-07-18-og-image-and-ai-sdk-refactor-design.md` (Part 2).
- **Spec amendment (approved direction, small deviation):** `AiSettings` keeps the non-secret `provider` property (needed to route SDK calls); only `api_key` is dropped. Provider options now come from configured providers, not a hardcoded list.
- Run tests with `vendor/bin/pest` (targeted) and `composer test` (full). Static analysis: `composer analyse`. Format: `composer format`.
- Code style: Pint Laravel preset, aligned `=>`, `new Foo` not `new Foo()`, no Yoda.

---

### Task 1: Branch, dependencies, and SDK API verification

**Files:**
- Modify: `composer.json`

**Interfaces:**
- Produces: `laravel/ai` installed as dev dependency; verified list of exact SDK class names/namespaces used by Tasks 3–4.

- [ ] **Step 1: Create the branch**

```bash
git checkout dev && git pull --ff-only && git checkout -b feature/laravel-ai-sdk
```

- [ ] **Step 2: Add laravel/ai to composer.json**

In `composer.json`, add a `suggest` section (none exists today) and a dev requirement:

```json
"suggest": {
    "laravel/ai": "Required for AI content generation features (^0.x)"
},
```

and in `require-dev` add:

```json
"laravel/ai": "0.*",
```

- [ ] **Step 3: Install**

Run: `composer update laravel/ai --with-all-dependencies`
Expected: resolves and installs. If it conflicts with Laravel 13 / illuminate 13, STOP and report the constraint error verbatim — do not force or downgrade anything.

- [ ] **Step 4: Verify the SDK surface the plan relies on**

Run these and record the results (they confirm names used in Tasks 3–4; adjust those tasks' imports if vendor reality differs):

```bash
grep -rn "interface Agent" vendor/laravel/ai/src/Contracts/Agent.php
grep -n "function maxTokens\|function temperature\|MaxTokensAttribute\|TemperatureAttribute\|method_exists" vendor/laravel/ai/src/Promptable.php | head -20
ls vendor/laravel/ai/src/Files/ 2>/dev/null || grep -rln "class Image" vendor/laravel/ai/src | head
grep -rn "public static function fromUrl\|public static function fromPath\|public static function fromBase64" vendor/laravel/ai/src | head
grep -rn "class TextDelta" vendor/laravel/ai/src/Streaming/Events/TextDelta.php
grep -n "function agent(" vendor/laravel/ai/src/functions.php vendor/laravel/ai/src/helpers.php 2>/dev/null
```

Expected findings (used later): `Laravel\Ai\Contracts\Agent`, `Laravel\Ai\Promptable`, image attachment classes (e.g. `Image::fromUrl(...)` / `Image::fromPath(...)` or `RemoteImage`/`LocalImage`), streaming event `Laravel\Ai\Streaming\Events\TextDelta` with `->delta`, and the `Laravel\Ai\agent()` helper. **If `Promptable` has no method fallback for max tokens/temperature, note it and omit those from `CmsAgent` (attributes with fixed defaults are acceptable; per-prompt max_tokens/temperature config is then dropped).**

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: add laravel/ai as suggested + dev dependency"
```

---

### Task 2: Rewrite AiFeatureDetector

**Files:**
- Modify: `src/Services/AiFeatureDetector.php`
- Test: `tests/Unit/AiFeatureDetectorTest.php`

**Interfaces:**
- Produces: `AiFeatureDetector::isInstalled(): bool`, `AiFeatureDetector::configuredProviders(): array` (provider-name => label), `AiFeatureDetector::isAvailable(): bool`. `isPrismInstalled()` is deleted.

- [ ] **Step 1: Write the failing tests**

Replace `tests/Unit/AiFeatureDetectorTest.php` entirely:

```php
<?php

use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Settings\AiSettings;

describe('isInstalled', function () {
    test('returns true when laravel/ai is installed', function () {
        // laravel/ai is a dev dependency, so it is present in the test env
        expect(AiFeatureDetector::isInstalled())->toBeTrue();
    });
});

describe('configuredProviders', function () {
    test('returns empty array when no provider has credentials', function () {
        config()->set('ai.providers', [
            'openai'    => ['driver' => 'openai', 'key' => null],
            'anthropic' => ['driver' => 'anthropic', 'key' => ''],
        ]);

        expect(AiFeatureDetector::configuredProviders())->toBe([]);
    });

    test('returns providers that have a non-empty key', function () {
        config()->set('ai.providers', [
            'openai'    => ['driver' => 'openai', 'key' => 'sk-test'],
            'anthropic' => ['driver' => 'anthropic', 'key' => null],
        ]);

        expect(AiFeatureDetector::configuredProviders())->toBe(['openai' => 'Openai']);
    });

    test('includes ollama only when explicitly enabled in franken-cms config', function () {
        config()->set('ai.providers', [
            'ollama' => ['driver' => 'ollama', 'base_url' => 'http://localhost:11434'],
        ]);

        config()->set('franken-cms.ai.enable_ollama', false);
        expect(AiFeatureDetector::configuredProviders())->toBe([]);

        config()->set('franken-cms.ai.enable_ollama', true);
        expect(AiFeatureDetector::configuredProviders())->toBe(['ollama' => 'Ollama']);
    });
});

describe('isAvailable', function () {
    test('returns false when no provider is configured', function () {
        config()->set('ai.providers', []);

        expect(AiFeatureDetector::isAvailable())->toBeFalse();
    });

    test('returns false when settings are disabled even with a configured provider', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = false;
        $settings->save();

        expect(AiFeatureDetector::isAvailable())->toBeFalse();
    });

    test('returns true when installed, configured, and enabled', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        expect(AiFeatureDetector::isAvailable())->toBeTrue();
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/AiFeatureDetectorTest.php`
Expected: FAIL — `isInstalled`/`configuredProviders` undefined.

- [ ] **Step 3: Rewrite the detector**

Replace `src/Services/AiFeatureDetector.php` body:

```php
<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Settings\AiSettings;
use Illuminate\Support\Str;

class AiFeatureDetector
{
    /**
     * Check if AI features are available (installed, configured, and enabled)
     */
    public static function isAvailable(): bool
    {
        if (! self::isInstalled()) {
            return false;
        }

        if (empty(self::configuredProviders())) {
            return false;
        }

        try {
            return app(AiSettings::class)->enabled;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if the laravel/ai SDK is installed
     */
    public static function isInstalled(): bool
    {
        return class_exists(\Laravel\Ai\Ai::class);
    }

    /**
     * Providers from config/ai.php that have credentials configured.
     * Ollama needs no key, so it is opt-in via franken-cms.ai.enable_ollama.
     *
     * @return array<string, string> provider name => display label
     */
    public static function configuredProviders(): array
    {
        return collect(config('ai.providers', []))
            ->filter(function (array $provider, string $name) {
                if (($provider['driver'] ?? null) === 'ollama') {
                    return (bool) config('franken-cms.ai.enable_ollama', false);
                }

                return ! empty($provider['key']);
            })
            ->mapWithKeys(fn (array $provider, string $name) => [$name => Str::title($name)])
            ->all();
    }
}
```

Note: if `\Laravel\Ai\Ai` does not exist per Task 1 Step 4 findings, use the SDK's actual root class or `Laravel\Ai\AiServiceProvider`.

- [ ] **Step 4: Add the ollama flag to package config**

In `config/franken-cms.php`, add after the existing `'settings'` key:

```php
'ai' => [
    // Ollama has no API key; opt in explicitly to expose it as a provider.
    'enable_ollama' => env('CMS_AI_ENABLE_OLLAMA', false),
],
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/AiFeatureDetectorTest.php`
Expected: PASS. (If `AiSettings` resolution fails in Unit tests because the settings table/migration isn't loaded, move the two `isAvailable` settings tests into `tests/Feature/AiFeatureDetectorTest.php` following how other Feature tests bootstrap settings.)

- [ ] **Step 6: Commit**

```bash
git add src/Services/AiFeatureDetector.php config/franken-cms.php tests/Unit/AiFeatureDetectorTest.php
git commit -m "feat: detect AI availability from laravel/ai config instead of Prism"
```

---

### Task 3: CmsAgent

**Files:**
- Create: `src/Ai/CmsAgent.php`
- Test: `tests/Unit/CmsAgentTest.php`

**Interfaces:**
- Produces: `FrankenCms\Ai\CmsAgent::__construct(string $instructions = ..., ?int $maxTokens = null, ?float $temperature = null)`; implements the SDK `Agent` contract via `Promptable`, so callers use `$agent->prompt(...)` / `$agent->stream(...)` with `provider:`/`model:` named args.

- [ ] **Step 1: Write the failing test**

```php
<?php

use FrankenCms\Ai\CmsAgent;
use Laravel\Ai\Contracts\Agent;

test('implements the SDK Agent contract', function () {
    expect(new CmsAgent)->toBeInstanceOf(Agent::class);
});

test('exposes constructor config through Promptable fallback methods', function () {
    $agent = new CmsAgent(
        instructions: 'Write plainly.',
        maxTokens: 750,
        temperature: 0.4,
    );

    expect($agent->instructions())->toBe('Write plainly.')
        ->and($agent->maxTokens())->toBe(750)
        ->and($agent->temperature())->toBe(0.4);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/CmsAgentTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement CmsAgent**

Create `src/Ai/CmsAgent.php` (adjust trait/contract imports to Task 1 Step 4 findings):

```php
<?php

namespace FrankenCms\Ai;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class CmsAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $instructions = 'You are a helpful assistant generating content for a CMS. Respond with only the requested content, no preamble.',
        protected ?int $maxTokens = null,
        protected ?float $temperature = null,
    ) {}

    public function instructions(): string
    {
        return $this->instructions;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function temperature(): ?float
    {
        return $this->temperature;
    }
}
```

If Task 1 Step 4 showed `Promptable` has no `maxTokens()`/`temperature()` method fallback, keep the methods (harmless accessors, tests still pass) but note in the class docblock that the SDK ignores them, and remove `temperature`/`max_tokens` plumbing from Task 4 accordingly.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/CmsAgentTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Ai/CmsAgent.php tests/Unit/CmsAgentTest.php
git commit -m "feat: add CmsAgent wrapping laravel/ai Promptable agent"
```

---

### Task 4: Rewrite AiService on the SDK

**Files:**
- Modify: `src/Services/AiService.php`
- Test: `tests/Unit/AiServiceTest.php`

**Interfaces:**
- Consumes: `CmsAgent` (Task 3), `AiFeatureDetector::isAvailable()/isInstalled()` (Task 2), `PromptManager::getPrompt(string): array` and `formatPrompt(string, array): string` (existing).
- Produces: unchanged public API — `generate(string $actionKey, array $context, ?callable $streamCallback = null): string` and `testConnection(): bool`. Consumers (`BaseAiAction`, `GenerateAltTextAction`, `GenerateBlogPostAction`, `GenerateImageTitleAction`, `BlogPostWizard`) must not need changes.

- [ ] **Step 1: Update the tests**

Replace `tests/Unit/AiServiceTest.php`:

```php
<?php

use FrankenCms\Prompts\PromptManager;
use FrankenCms\Services\AiService;

beforeEach(function () {
    $this->promptManager = new PromptManager;
    $this->service = new AiService($this->promptManager);
});

describe('generate', function () {
    test('throws exception when AI features are not available', function () {
        config()->set('ai.providers', []); // nothing configured

        $this->service->generate('generate_seo_title', ['title' => 'Test']);
    })->throws(Exception::class, 'AI features are not available');
});

describe('testConnection', function () {
    test('returns false when no provider is configured', function () {
        config()->set('ai.providers', []);

        expect($this->service->testConnection())->toBeFalse();
    });
});

describe('constructor', function () {
    test('accepts PromptManager dependency', function () {
        expect(new AiService($this->promptManager))->toBeInstanceOf(AiService::class);
    });
});
```

- [ ] **Step 2: Run tests to verify the new expectations fail**

Run: `vendor/bin/pest tests/Unit/AiServiceTest.php`
Expected: FAIL — current implementation references Prism classes (now absent) / old detection.

- [ ] **Step 3: Rewrite AiService**

Replace `src/Services/AiService.php` (image attachment class/factories per Task 1 Step 4 — the code below assumes `Laravel\Ai\Files\Image` with `fromUrl`/`fromPath` factories; substitute the verified names):

```php
<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Ai\CmsAgent;
use FrankenCms\Prompts\PromptManager;
use FrankenCms\Settings\AiSettings;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Streaming\Events\TextDelta;

class AiService
{
    public function __construct(
        protected PromptManager $promptManager
    ) {}

    /**
     * Generate content using AI with optional streaming
     *
     * @param  callable|null  $streamCallback  Optional callback for streaming (receives string chunks)
     *
     * @throws Exception
     */
    public function generate(string $actionKey, array $context, ?callable $streamCallback = null): string
    {
        if (! AiFeatureDetector::isAvailable()) {
            throw new Exception('AI features are not available. Install laravel/ai, set a provider API key in your .env, and enable Igor in settings.');
        }

        $promptConfig = $this->promptManager->getPrompt($actionKey);

        $imageUrl = $context['image_url'] ?? null;
        $imagePath = $context['image_path'] ?? null;
        $isVisionPrompt = ($promptConfig['supports_vision'] ?? false) && ($imageUrl || $imagePath);

        unset($context['image_url'], $context['image_path']);

        $formattedPrompt = $this->promptManager->formatPrompt(
            $promptConfig['prompt'],
            $context
        );

        $settings = app(AiSettings::class);

        $agent = new CmsAgent(
            maxTokens: $promptConfig['max_tokens'] ?? 500,
            temperature: $promptConfig['temperature'] ?? null,
        );

        $attachments = $isVisionPrompt ? $this->buildImageAttachments($imageUrl, $imagePath) : [];

        try {
            if ($streamCallback !== null) {
                $fullText = '';
                $stream = $agent->stream(
                    $formattedPrompt,
                    $attachments,
                    provider: $settings->provider,
                    model: $settings->model,
                );

                foreach ($stream as $event) {
                    if ($event instanceof TextDelta) {
                        $fullText .= $event->delta;
                        $streamCallback($event->delta);
                    }
                }

                return trim($fullText);
            }

            $response = $agent->prompt(
                $formattedPrompt,
                $attachments,
                provider: $settings->provider,
                model: $settings->model,
            );

            return trim($response->text);
        } catch (Exception $e) {
            throw new Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Test provider connection
     */
    public function testConnection(): bool
    {
        if (! AiFeatureDetector::isInstalled()) {
            return false;
        }

        try {
            $settings = app(AiSettings::class);

            if (! array_key_exists($settings->provider, AiFeatureDetector::configuredProviders())) {
                return false;
            }

            $response = (new CmsAgent(maxTokens: 10))->prompt(
                'Respond with only the word "OK"',
                provider: $settings->provider,
                model: $settings->model,
            );

            return ! empty($response->text);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Build SDK image attachments, preferring a public URL outside local dev
     *
     * @return array<int, Image>
     */
    protected function buildImageAttachments(?string $imageUrl, ?string $imagePath): array
    {
        if ($imageUrl && ! app()->environment('local')) {
            return [Image::fromUrl($imageUrl)];
        }

        if ($imagePath && file_exists($imagePath)) {
            return [Image::fromPath($imagePath)];
        }

        return [];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/AiServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Services/AiService.php tests/Unit/AiServiceTest.php
git commit -m "feat: rewrite AiService on the laravel/ai SDK"
```

---

### Task 5: Drop the stored API key from settings

**Files:**
- Modify: `src/Settings/AiSettings.php`
- Modify: `database/migrations/19_create_ai_settings.php`
- Modify: `src/SettingsTabs/AiSettingsTabProvider.php`
- Test: existing Feature settings tests (`vendor/bin/pest tests/Feature --filter=Settings`) plus `tests/Feature/AiSettingsTabTest.php` if present (check with `ls tests/Feature | grep -i ai`).

**Interfaces:**
- Consumes: `AiFeatureDetector::configuredProviders()` (Task 2).
- Produces: `AiSettings` without `api_key` (keeps `provider`, `model`, `enabled`, per-feature toggles/prompts). Settings tab shows env-setup guidance when nothing is configured.

- [ ] **Step 1: Edit AiSettings**

In `src/Settings/AiSettings.php`: delete the `public ?string $api_key = null;` property, the `use FrankenCms\SettingsCasts\EncryptedSettingsCast;` import, and the entire `casts()`/`$casts` entry mapping `'api_key' => EncryptedSettingsCast::class` (delete the whole casts method if `api_key` was its only entry). Keep `EncryptedSettingsCast` class itself and its test — it is a general-purpose cast.

- [ ] **Step 2: Edit the create migration in place (beta, no BC)**

In `database/migrations/19_create_ai_settings.php`, delete the line that adds `cms_ai.api_key` (e.g. `$this->migrator->addEncrypted('cms_ai.api_key', null);` or `$this->migrator->add('cms_ai.api_key', ...)` — match what is there). Leave `provider` and `model` lines intact.

Then create `database/migrations/22_remove_ai_api_key_setting.php` so existing beta installs shed the stored key (a security cleanup, per spec):

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('cms_ai.api_key');
    }
};
```

(Match the base-class and file-naming conventions of the neighboring migrations — e.g. `21_add_post_slug_unique_index.php` — and check `deleteIfExists` exists on the migrator; if not, use `$this->migrator->delete('cms_ai.api_key')` guarded by `$this->migrator->exists(...)`.)

- [ ] **Step 3: Rework the settings tab**

In `src/SettingsTabs/AiSettingsTabProvider.php`:

1. Delete the `TextInput::make('api_key')` component (lines ~83–107) including its `dehydrateStateUsing`/`required`/`visible` closures.
2. Change the provider select (line ~74) options to configured providers and add a no-provider guidance placeholder above it:

```php
use Filament\Forms\Components\Placeholder;
use FrankenCms\Services\AiFeatureDetector;
use Illuminate\Support\HtmlString;
```

```php
Placeholder::make('ai_setup_notice')
    ->hiddenLabel()
    ->content(new HtmlString(
        '<strong>No AI provider configured.</strong> Add an API key to your <code>.env</code> '
        . '(e.g. <code>OPENAI_API_KEY</code>, <code>ANTHROPIC_API_KEY</code>, or <code>GEMINI_API_KEY</code>) '
        . 'and publish <code>config/ai.php</code> if you need to customize providers. '
        . 'Keys are no longer stored in the database.'
    ))
    ->visible(fn () => empty(AiFeatureDetector::configuredProviders())),

Select::make('provider')
    ->label('Provider')
    ->options(fn () => AiFeatureDetector::configuredProviders())
    ->live()
    ->visible(fn ($get) => $get('enabled') && ! empty(AiFeatureDetector::configuredProviders())),
```

(Keep any existing helper text/labels on the provider select that still make sense; delete the old `getProviderOptions()` method reading `config('prism.providers')`, lines ~299–322.)

3. Model select (line ~109): replace `->options(fn ($get) => $this->getModelsForProvider($get('provider'), $get('api_key')))` with `->options(fn ($get) => $this->getModelsForProvider($get('provider')))`, drop the `$apiKey` parameter from the protected `getModelsForProvider()` method, and update the `refresh_models` action to call `$this->refreshModels($get('provider'), $livewire)` with its `->visible()` becoming `fn ($get) => $get('enabled') && array_key_exists($get('provider'), AiFeatureDetector::configuredProviders())`. `refreshModels()` loses its `$apiKey` parameter too (Task 6 changes `AiModelService` to read keys from config).

- [ ] **Step 4: Run the settings tests**

Run: `vendor/bin/pest tests/Feature --filter=Ai`
Expected: PASS (fix any test still referencing `api_key` on `AiSettings` by deleting that assertion — the property is intentionally gone).

- [ ] **Step 5: Commit**

```bash
git add src/Settings/AiSettings.php database/migrations/19_create_ai_settings.php src/SettingsTabs/AiSettingsTabProvider.php tests
git commit -m "feat!: drop stored AI api_key; keys now live in .env per laravel/ai convention"
```

---

### Task 6: AiModelService reads keys from config/ai.php

**Files:**
- Modify: `src/Services/AiModelService.php`
- Test: `tests/Unit/AiModelServiceTest.php` (create if absent; check `ls tests/Unit | grep -i model`)

**Interfaces:**
- Produces: `getModelsForProvider(string $provider): array`, `refreshModels(string $provider): array` — `$apiKey` parameters removed; key resolved internally via `config("ai.providers.{$provider}.key")`. Cache behavior (`clearCache`, `hasCachedModels`) unchanged.

- [ ] **Step 1: Write/adjust the test**

Create `tests/Unit/AiModelServiceTest.php`:

```php
<?php

use FrankenCms\Services\AiModelService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new AiModelService;
    $this->service->clearCache();
});

test('getModelsForProvider returns curated fallback list without an API call when no key is configured', function () {
    config()->set('ai.providers.openai.key', null);
    Http::fake();

    $models = $this->service->getModelsForProvider('openai');

    expect($models)->toBeArray()->not->toBeEmpty();
    Http::assertNothingSent();
});

test('refreshModels fetches from provider API using the config key', function () {
    config()->set('ai.providers.openai.key', 'sk-test');
    Http::fake([
        'api.openai.com/*' => Http::response(['data' => [['id' => 'gpt-4o']]]),
    ]);

    $models = $this->service->refreshModels('openai');

    expect($models)->toHaveKey('gpt-4o');
});
```

(Adjust the fake response shape to match the existing `fetchOpenAiModels()` parsing — read it first; it starts at line 120.)

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/AiModelServiceTest.php`
Expected: FAIL — signatures still require `$apiKey`.

- [ ] **Step 3: Refactor AiModelService**

In `src/Services/AiModelService.php`:

1. Change `getModelsForProvider(string $provider, ?string $apiKey = null)` → `getModelsForProvider(string $provider)`; `refreshModels(string $provider, string $apiKey)` → `refreshModels(string $provider)`.
2. Add a private key resolver and use it everywhere `$apiKey` was used:

```php
protected function resolveKey(string $provider): ?string
{
    return config("ai.providers.{$provider}.key");
}
```

3. `fetchModelsFromApi(string $provider, string $apiKey)` → `fetchModelsFromApi(string $provider)` calling `$this->resolveKey($provider)`; skip the API call (return the curated fallback list) when the key is empty and the provider is not `ollama`. The per-provider `fetch*` methods keep their `$apiKey` params, now fed from `resolveKey()`.
4. Delete any references to `AiSettings->api_key` in this file.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/AiModelServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Services/AiModelService.php tests/Unit/AiModelServiceTest.php
git commit -m "refactor: AiModelService resolves provider keys from config/ai.php"
```

---

### Task 7: Purge remaining Prism references

**Files:**
- Modify: `src/FrankenCmsServiceProvider.php` (lines ~63, ~155-160, ~269-275, ~529-533)
- Modify: `src/Helpers/MiscHelpers.php`
- Modify: any file surfaced by the final grep

**Interfaces:**
- Consumes: `AiFeatureDetector::isInstalled()` (Task 2).
- Produces: zero `Prism`/`prism` references in `src/`, `config/`, `resources/`, `docs/`, `README.md`.

- [ ] **Step 1: Service provider gates**

In `src/FrankenCmsServiceProvider.php`: remove `use Prism\Prism\Prism;` (line 63); replace all three `class_exists(Prism::class)` checks (registration of AI singletons ~line 156, `registerLivewireComponents` ~line 270, `registerAiModal` ~line 530) with `AiFeatureDetector::isInstalled()` (import `FrankenCms\Services\AiFeatureDetector` — check it isn't already imported).

- [ ] **Step 2: Helper rename**

In `src/Helpers/MiscHelpers.php`, replace `is_prism_installed()`:

```php
public static function is_ai_installed(): bool
{
    return \FrankenCms\Services\AiFeatureDetector::isInstalled();
}
```

Then find and update all callers: `grep -rn "is_prism_installed" src resources tests` — update each call site to `is_ai_installed`.

- [ ] **Step 3: Sweep for stragglers**

Run: `grep -rin "prism" src config resources docs README.md tests composer.json`
Expected: no hits. Fix any that appear (user-facing copy in the AI settings tab, Igor installer messages in `src/Support/IgorMessages.php`, Blade views, docblocks). User-facing copy should now say "laravel/ai" / "the Laravel AI SDK".

- [ ] **Step 4: Run the full suite**

Run: `composer test`
Expected: PASS, no errors.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: remove all Prism references in favor of laravel/ai"
```

---

### Task 8: Static analysis, formatting, docs, final verification

**Files:**
- Modify: `README.md` (AI section)
- Modify: anything PHPStan/Pint flags

- [ ] **Step 1: Update README**

Find the AI section (`grep -n -i "prism\|ai" README.md`) and rewrite it: installation is `composer require laravel/ai`, keys go in `.env` (`OPENAI_API_KEY` / `ANTHROPIC_API_KEY` / `GEMINI_API_KEY` / others per `config/ai.php`), optional `php artisan vendor:publish` for `config/ai.php`, Ollama via `CMS_AI_ENABLE_OLLAMA=true`, then enable Igor in CMS Settings → AI. Note explicitly: API keys are no longer stored in the database.

- [ ] **Step 2: Static analysis and formatting**

Run: `composer analyse` — Expected: no errors (fix any).
Run: `composer format` — Expected: clean or auto-fixed.

- [ ] **Step 3: Full suite one more time**

Run: `composer test`
Expected: PASS.

- [ ] **Step 4: Commit and push the branch**

```bash
git add -A
git commit -m "docs: document laravel/ai setup; analysis and formatting pass"
git push -u origin feature/laravel-ai-sdk
```

- [ ] **Step 5: Manual smoke test note**

Report to the user: the test app at `/Users/mikewall/Sites/frankecms` needs `composer require laravel/ai`, an API key in `.env`, and its stale `cms_ai.api_key` settings row removed (or `php artisan migrate:fresh`) before the AI features can be exercised end-to-end there. Do not modify the test app without being asked.
