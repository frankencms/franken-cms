# OG Image Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate `spatie/laravel-og-image` as FrankenCMS's optional OG image generation engine, with per-post-type templates from config, manual uploads and the site default as fallbacks, and zero duplicate meta tags.

**Architecture:** A self-contained `src/OgImage/` module (Approach A from the spec). `OgImageFeature` gates everything (installed + enabled) and resolves which branch a page gets. A `<x-franken-og-image />` Blade component (dropped once into the theme layout) delegates every branch to Spatie's native `<x-og-image>` component: mapped template → `view=`/`:data=`, manual upload or site default → `:url=` passthrough (skips generation). When the feature is active for a page, the Spatie middleware owns `og:image`/`twitter:image`/`twitter:card` injection and `AddSeoDefaults` suppresses those three tags; otherwise today's behavior is untouched. Cloudflare Browser Rendering is a one-place env config.

**Tech Stack:** PHP 8.4, Laravel 13 (Orchestra Testbench), `spatie/laravel-og-image` (dev dependency for tests, `suggest` for consumers), Pest v4, PHPStan level 5 (`--memory-limit=1G` on this machine), Pint.

## Global Constraints

- Branch: `feature/og-image-integration` off `dev`. Never push to `main`. `dev` now exists on origin.
- No BC required (beta). Spec: `docs/superpowers/specs/2026-07-18-og-image-and-ai-sdk-refactor-design.md` (Part 1).
- **Spec amendment (justified by package capabilities discovered after spec approval):** the spec said `AddSeoDefaults` stays the single owner of `og:image`/`twitter:image`, pointing at Spatie's URL. The package's middleware natively injects those tags (plus `twitter:card`) for pages carrying an `<x-og-image>` component, and the component accepts a passthrough `:url` for pre-existing images — so fighting that pipeline would mean re-deriving content hashes ourselves. Instead: when `OgImageFeature` resolves for a page, `AddSeoDefaults` skips emitting `og:image`, `twitter:image`, and `twitter:card`, and the Spatie pipeline (fed by our component) owns them. When the feature is unavailable, disabled, or resolves nothing for the page — including posts that opt into Twitter summary cards — behavior is byte-for-byte today's. No duplicate tags either way (the user-visible invariant the spec was protecting).
- Resolution precedence (user decision): mapped per-post-type template → manual per-post upload (`seo-og`) → site default (`og-default`) → nothing.
- Posts with `seo_use_twitter_summary` = true keep the classic (non-generated) path so their `summary` card is honored.
- Generation failures must never break page rendering (the component/feature degrade to nothing; Spatie handles its own screenshot errors server-side on the image route, not inline).
- Run tests with `vendor/bin/pest` (targeted) and `composer test` (full). Analysis: `vendor/bin/phpstan analyse --memory-limit=1G` (~238 pre-existing errors elsewhere are out of scope). Format: `composer format`.
- Pint: aligned `=>`, `new Foo` not `new Foo()`, no Yoda.

Verified package facts (re-verify exact FQCNs in Task 1): `<x-og-image>` component accepts inline slot, `view="..."` + `:data="[...]"`, or `:url="..."` (passthrough, no screenshot); middleware auto-registers in the `web` group and injects `og:image` + `twitter:image` + `twitter:card` for pages with a component; facade `Spatie\OgImage\Facades\OgImage` with `useCloudflare(apiToken:, accountId:)`, `fallbackUsing()`, `generateForUrl()`; config `config/og-image.php` (disk, path, width 1200, height 630, format, quality).

Existing codebase facts: components register via `Blade::component('name', Class::class)` in `FrankenCmsServiceProvider` (~line 199, alongside `cms-field`, `breadcrumbs`); `CurrentPageService::getPage(): ?Post`; `Post::getFirstMedia('seo-og')` is the post-specific upload (`Post::seoOgImage()` already folds in the site default — do NOT use it for branch separation); site default lives on `SiteSettingsMedia` collection `og-default` with conversion `og`; `AddSeoDefaults::includeOpenGraph()/includeTwitter()` emit the tags; example theme stubs live in `stubs/theme/` with the layout at `stubs/theme/components/layouts/main/index.blade.php`; installer example-theme step is at `src/Commands/InstallCommand.php` ~line 724.

---

### Task 1: Branch, dependency, and package API verification

**Files:**
- Modify: `composer.json`

**Interfaces:**
- Produces: `spatie/laravel-og-image` installed as dev dependency; verified FQCNs/attributes for Tasks 2–4.

- [ ] **Step 1: Create the branch**

```bash
git checkout dev && git pull --ff-only && git checkout -b feature/og-image-integration
```

- [ ] **Step 2: Add the dependency**

In `composer.json`, extend the existing `suggest` block and `require-dev`:

```json
"suggest": {
    "laravel/ai": "Required for AI content generation features (^0.x)",
    "spatie/laravel-og-image": "Recommended for automatic Open Graph image generation from Blade templates"
},
```

and in `require-dev` add (keep alphabetical sort):

```json
"spatie/laravel-og-image": "*",
```

Tighten `*` to the resolved major (`^1.0`-style) after install succeeds.

- [ ] **Step 3: Install**

Run: `composer update spatie/laravel-og-image --with-all-dependencies`
Expected: resolves against Laravel 13. If it conflicts, STOP and report the constraint error verbatim — do not force or downgrade.

- [ ] **Step 4: Verify the package surface the plan relies on**

Record findings (Tasks 2–4 adjust to them):

```bash
ls vendor/spatie/laravel-og-image/src/
grep -rn "class OgImage\|function useCloudflare\|function fallbackUsing" vendor/spatie/laravel-og-image/src/ | head
find vendor/spatie/laravel-og-image -name "*.blade.php" -o -name "*Component*.php" | head
grep -rn "props\|attributes\[.url.\]\|'url'\|'view'\|'data'" vendor/spatie/laravel-og-image/src/Components/ vendor/spatie/laravel-og-image/resources/ 2>/dev/null | head -20
grep -rn "class .*Middleware\|pushMiddlewareToGroup\|prependMiddlewareToGroup" vendor/spatie/laravel-og-image/src/ | head
cat vendor/spatie/laravel-og-image/config/og-image.php
```

Key questions to answer in the report: (a) exact component name/class and whether `url`/`view`/`data` attributes exist as assumed; (b) the middleware class + whether it injects `twitter:card` and skips pages without a component; (c) a concrete class for `class_exists()` detection (prefer a small always-loaded one, e.g. the service provider); (d) does the package expose a way to get the generated URL programmatically for the current page (not needed by this plan, but record it); (e) the config keys (disk/path/format) for the installer step.

- [ ] **Step 5: Commit**

```bash
git add composer.json
git commit -m "chore: add spatie/laravel-og-image as suggested + dev dependency"
```

---

### Task 2: OgImageFeature gate + config

**Files:**
- Create: `src/OgImage/OgImageFeature.php`
- Modify: `config/franken-cms.php`
- Modify: `src/FrankenCmsServiceProvider.php` (Cloudflare boot hook)
- Test: `tests/Unit/OgImageFeatureTest.php`

**Interfaces:**
- Produces: `OgImageFeature::isInstalled(): bool`; `isEnabled(): bool` (installed && config enabled); `templateFor(?Post $post): ?string` (mapped Blade view name for the post's type, only if the view exists); `resolvesFor(?Post $post): bool` (true when the page will carry an og-image component: template mapped OR post has `seo-og` media OR site default `og-default` exists — but false when `$post?->getMeta('seo_use_twitter_summary', ...)` prefers a summary card, mirroring `AddSeoDefaults::includeTwitter()`'s existing per-post/global fallback logic).

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/OgImageFeatureTest.php`:

```php
<?php

use FrankenCms\Models\Post;
use FrankenCms\OgImage\OgImageFeature;

describe('isInstalled', function () {
    test('returns true when spatie/laravel-og-image is installed', function () {
        // dev dependency, present in the test env
        expect(OgImageFeature::isInstalled())->toBeTrue();
    });
});

describe('isEnabled', function () {
    test('respects the config toggle', function () {
        config()->set('franken-cms.og_image.enabled', false);
        expect(OgImageFeature::isEnabled())->toBeFalse();

        config()->set('franken-cms.og_image.enabled', true);
        expect(OgImageFeature::isEnabled())->toBeTrue();
    });
});

describe('templateFor', function () {
    test('returns null when no template is mapped for the post type', function () {
        config()->set('franken-cms.og_image.templates', []);
        $post = Post::factory()->create();

        expect(OgImageFeature::templateFor($post))->toBeNull();
    });

    test('returns null when the mapped view does not exist', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'theme.og-templates.missing']);
        $post = Post::factory()->create();

        expect(OgImageFeature::templateFor($post))->toBeNull();
    });

    test('returns the mapped view when it exists', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();

        expect(OgImageFeature::templateFor($post))->toBe('franken-cms::help');
    });

    test('returns null for a null post', function () {
        expect(OgImageFeature::templateFor(null))->toBeNull();
    });
});

describe('resolvesFor', function () {
    test('is false when nothing resolves', function () {
        config()->set('franken-cms.og_image.templates', []);
        $post = Post::factory()->create();

        expect(OgImageFeature::resolvesFor($post))->toBeFalse();
    });

    test('is true when a template is mapped', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();

        expect(OgImageFeature::resolvesFor($post))->toBeTrue();
    });

    test('is false when the post prefers a twitter summary card', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();
        $post->setMeta('seo_use_twitter_summary', true);

        expect(OgImageFeature::resolvesFor($post))->toBeFalse();
    });
});
```

Adjust factory/meta calls to the patterns used in existing tests (see `tests/Unit/SeoImageFallbackTest.php` for how posts + media + settings are set up; `franken-cms::help` is an existing package view usable as an exists-check stand-in — verify with `view()->exists('franken-cms::help')`, otherwise pick any registered package view).

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/OgImageFeatureTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement the gate**

Create `src/OgImage/OgImageFeature.php`:

```php
<?php

namespace FrankenCms\OgImage;

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Settings\SeoSettings;

class OgImageFeature
{
    /**
     * Check if spatie/laravel-og-image is installed
     */
    public static function isInstalled(): bool
    {
        return class_exists(\Spatie\OgImage\OgImageServiceProvider::class);
    }

    /**
     * Installed and enabled in config
     */
    public static function isEnabled(): bool
    {
        return self::isInstalled() && (bool) config('franken-cms.og_image.enabled', true);
    }

    /**
     * The Blade view mapped to this post's type, if it exists
     */
    public static function templateFor(?Post $post): ?string
    {
        if (! $post) {
            return null;
        }

        $view = config("franken-cms.og_image.templates.{$post->post_type}");

        return ($view && view()->exists($view)) ? $view : null;
    }

    /**
     * Whether the current page will carry an og-image component.
     * Mirrors the component's branch logic so AddSeoDefaults can defer
     * tag ownership to the Spatie middleware without duplicates.
     */
    public static function resolvesFor(?Post $post): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        // Summary-card posts keep the classic tag path (spatie always emits summary_large_image)
        $usesSummary = $post
            ? $post->getMeta('seo_use_twitter_summary', app(SeoSettings::class)->use_twitter_summary_card)
            : app(SeoSettings::class)->use_twitter_summary_card;

        if ($usesSummary) {
            return false;
        }

        if (self::templateFor($post)) {
            return true;
        }

        if ($post?->getFirstMedia('seo-og')) {
            return true;
        }

        return SiteSettingsMedia::instance()->hasMedia('og-default');
    }
}
```

Adjust the detection class to Task 1's findings and `SiteSettingsMedia::instance()` to however the codebase actually resolves it (check `SeoService::getOgImage()`'s existing site-default lookup around `src/Services/SeoService.php:160` and copy that exact mechanism).

- [ ] **Step 4: Add config + Cloudflare boot hook**

In `config/franken-cms.php`, after the `'ai'` section:

```php
/*
|--------------------------------------------------------------------------
| OG Image Generation
|--------------------------------------------------------------------------
|
| Requires spatie/laravel-og-image (composer require spatie/laravel-og-image).
| Map post types to Blade views containing an <x-og-image> component; add
| <x-franken-og-image /> to your theme layout once. Manual per-post uploads
| and the site default image are used automatically as fallbacks.
| For hosts without Chrome/Node, set the Cloudflare credentials below to
| render via Cloudflare's Browser Rendering API.
|
*/

'og_image' => [
    'enabled'   => true,
    'templates' => [
        // 'post' => 'theme.og-templates.post',
        // 'page' => 'theme.og-templates.page',
    ],
    'cloudflare' => [
        'api_token'  => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
    ],
],
```

In `FrankenCmsServiceProvider` boot path, add (near the other conditional registrations):

```php
private function registerOgImageRendering(): void
{
    if (! OgImageFeature::isInstalled()) {
        return;
    }

    $cloudflare = config('franken-cms.og_image.cloudflare');

    if (! empty($cloudflare['api_token']) && ! empty($cloudflare['account_id'])) {
        \Spatie\OgImage\Facades\OgImage::useCloudflare(
            apiToken: $cloudflare['api_token'],
            accountId: $cloudflare['account_id'],
        );
    }
}
```

and call it from `boot()` following the pattern of the existing `register*` calls.

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/OgImageFeatureTest.php`
Expected: PASS. Then `composer test` — no regressions.

- [ ] **Step 6: Commit**

```bash
git add src/OgImage/OgImageFeature.php config/franken-cms.php src/FrankenCmsServiceProvider.php tests/Unit/OgImageFeatureTest.php
git commit -m "feat: add OgImageFeature gate, og_image config, and Cloudflare passthrough"
```

---

### Task 3: The `<x-franken-og-image />` component

**Files:**
- Create: `src/View/Components/OgImage.php`
- Create: `resources/views/components/og-image.blade.php`
- Modify: `src/FrankenCmsServiceProvider.php` (component registration, ~line 199)
- Test: `tests/Feature/OgImageComponentTest.php`

**Interfaces:**
- Consumes: `OgImageFeature::isEnabled()/templateFor()` (Task 2), `CurrentPageService::getPage()`.
- Produces: Blade component `<x-franken-og-image />` — renders exactly one of: Spatie `<x-og-image view="..." :data="['post' => $post]">`, `<x-og-image :url="...">` (manual upload `og` conversion URL), `<x-og-image :url="...">` (site default), or nothing.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/OgImageComponentTest.php` (follow existing Feature-test setup patterns for posts/media/settings; render with `Blade::render('<x-franken-og-image />')` after priming `CurrentPageService`):

```php
<?php

use FrankenCms\Models\Post;
use FrankenCms\Services\CurrentPageService;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    config()->set('franken-cms.og_image.enabled', true);
});

test('renders nothing when the feature is disabled', function () {
    config()->set('franken-cms.og_image.enabled', false);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});

test('renders the mapped template through the spatie component', function () {
    config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain('data-og-image');
});

test('passes a manual upload url through without generation', function () {
    config()->set('franken-cms.og_image.templates', []);
    $post = Post::factory()->create();
    // attach media to the seo-og collection per existing media test patterns
    $post->addMedia(base_path('tests/fixtures/test-image.jpg'))
        ->preservingOriginal()
        ->toMediaCollection('seo-og');
    app(CurrentPageService::class)->setPage($post);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain($post->getFirstMedia('seo-og')->getFullUrl('og'));
});

test('renders nothing when nothing resolves', function () {
    config()->set('franken-cms.og_image.templates', []);
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});
```

Adapt fixture paths/media attachment to whatever `tests/Feature/EnhancedImageTest.php` (or similar) already does — reuse its fixture image. If the spatie component's rendered output for the `:url` passthrough doesn't literally contain the URL, assert on whatever it does emit (inspect the vendor component view from Task 1's findings) — the assertion must pin real observable output, not be weakened to `not->toBe('')`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/OgImageComponentTest.php`
Expected: FAIL — unknown component `franken-og-image`.

- [ ] **Step 3: Implement**

Create `src/View/Components/OgImage.php`:

```php
<?php

namespace FrankenCms\View\Components;

use FrankenCms\Models\Post;
use FrankenCms\OgImage\OgImageFeature;
use FrankenCms\Services\CurrentPageService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OgImage extends Component
{
    public function render(): View|string
    {
        if (! OgImageFeature::isEnabled()) {
            return '';
        }

        $post = app(CurrentPageService::class)->getPage();

        if ($template = OgImageFeature::templateFor($post)) {
            return view('franken-cms::components.og-image', [
                'template' => $template,
                'url'      => null,
                'post'     => $post,
            ]);
        }

        if ($url = $this->manualUrl($post) ?? $this->defaultUrl()) {
            return view('franken-cms::components.og-image', [
                'template' => null,
                'url'      => $url,
                'post'     => $post,
            ]);
        }

        return '';
    }

    protected function manualUrl(?Post $post): ?string
    {
        return $post?->getFirstMedia('seo-og')?->getFullUrl('og');
    }

    protected function defaultUrl(): ?string
    {
        // copy the exact site-default lookup SeoService::getOgImage() uses
        // (SiteSettingsMedia + 'og-default' collection + 'og' conversion)
        return null; // replaced with the real lookup during implementation
    }
}
```

Create `resources/views/components/og-image.blade.php`:

```blade
@if ($template)
    <x-og-image :view="$template" :data="['post' => $post]" />
@elseif ($url)
    <x-og-image :url="$url" />
@endif
```

(Adjust attribute names to Task 1's verified component API — if the vendor component takes `view`/`data` as non-dynamic props or different names, match it exactly.) Register in `FrankenCmsServiceProvider` next to the existing components:

```php
Blade::component('franken-og-image', \FrankenCms\View\Components\OgImage::class);
```

Implement `defaultUrl()` by copying `SeoService::getOgImage()`'s site-default branch (around `src/Services/SeoService.php:160`) — same model, collection, and conversion.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/OgImageComponentTest.php` then `composer test`
Expected: PASS, no regressions.

- [ ] **Step 5: Commit**

```bash
git add src/View/Components/OgImage.php resources/views/components/og-image.blade.php src/FrankenCmsServiceProvider.php tests/Feature/OgImageComponentTest.php
git commit -m "feat: add franken-og-image component delegating to spatie og-image"
```

---

### Task 4: Meta tag ownership in AddSeoDefaults

**Files:**
- Modify: `src/Http/Middleware/AddSeoDefaults.php` (`includeOpenGraph()` ~line 112, `includeTwitter()` ~line 178)
- Test: `tests/Feature/OgImageMetaOwnershipTest.php`

**Interfaces:**
- Consumes: `OgImageFeature::resolvesFor(?Post)` (Task 2).
- Produces: when `resolvesFor($post)` is true, `AddSeoDefaults` emits NO `og:image`, NO `twitter:image`, NO `twitter:card` (Spatie middleware owns them); all other og:/twitter: tags unchanged. When false, today's behavior byte-for-byte.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/OgImageMetaOwnershipTest.php` — follow how existing SEO feature tests hit a front-end route and assert on rendered head tags (see `tests/Unit/SeoImageFallbackTest.php` and any Feature test that requests a page route):

```php
<?php

use FrankenCms\Models\Post;

test('classic path still emits og:image when the feature is disabled', function () {
    config()->set('franken-cms.og_image.enabled', false);
    // create post with an seo-og upload, request its page, assert response contains og:image meta
});

test('suppresses og:image, twitter:image and twitter:card when the feature resolves', function () {
    config()->set('franken-cms.og_image.enabled', true);
    config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
    // request the post page, assert the AddSeoDefaults-rendered head contains
    // og:title but NOT og:image / twitter:image / twitter:card from seo()
});

test('summary-card posts keep the classic tags even when a template is mapped', function () {
    config()->set('franken-cms.og_image.enabled', true);
    config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
    // post with seo_use_twitter_summary = true → twitter:card "summary" present
});
```

Flesh the bodies out against the real routing/test helpers (the fallback route renders theme views; if full-page rendering is heavy, assert via the `seo()` container contents after running the middleware directly — whichever pattern neighboring tests already use). The assertions must check the three suppressed tags AND that og:title/og:url survive.

- [ ] **Step 2: Run tests to verify the suppression cases fail**

Run: `vendor/bin/pest tests/Feature/OgImageMetaOwnershipTest.php`
Expected: suppression tests FAIL (tags currently always emitted); disabled-path test PASSES.

- [ ] **Step 3: Implement the suppression**

In `AddSeoDefaults`, compute once in `handle()` before the include calls:

```php
$ogImageHandledExternally = \FrankenCms\OgImage\OgImageFeature::resolvesFor($post);
```

Pass it to both methods (change their signatures — they're private). In `includeOpenGraph()`, wrap only the `og:image` block (`if ($image = $this->seoService->getOgImage($post))`) in `if (! $ogImageHandledExternally)`. In `includeTwitter()`, wrap the `twitter:card` emission AND the `twitter:image` block the same way. Leave `twitter:title`/`twitter:description`/`twitter:site` untouched.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/OgImageMetaOwnershipTest.php` then `composer test`
Expected: PASS, no regressions (especially `SeoImageFallbackTest`).

- [ ] **Step 5: Commit**

```bash
git add src/Http/Middleware/AddSeoDefaults.php tests/Feature/OgImageMetaOwnershipTest.php
git commit -m "feat: defer og:image ownership to spatie og-image when the feature resolves"
```

---

### Task 5: Example theme templates + Filament helper text

**Files:**
- Create: `stubs/theme/og-templates/post.blade.php`
- Create: `stubs/theme/og-templates/page.blade.php`
- Modify: `stubs/theme/components/layouts/main/index.blade.php` (add `<x-franken-og-image />` inside `<body>`)
- Modify: `src/Filament/Resources/Concerns/HasSeoFields.php` (helper text on the `seo_og_image` upload, ~line 290)

**Interfaces:**
- Consumes: `<x-franken-og-image />` (Task 3), `OgImageFeature::isEnabled()/templateFor()` (Task 2).

- [ ] **Step 1: Create the OG templates**

`stubs/theme/og-templates/post.blade.php` (1200×630 canvas; templates inherit the page's Tailwind build per the package's design — match the example theme's styling conventions):

```blade
<x-og-image>
    <div class="w-full h-full bg-zinc-900 text-white flex flex-col justify-between p-16">
        <div class="flex items-center gap-4">
            @if ($post->featuredImage())
                <img src="{{ $post->featuredImage()->getFullUrl() }}" class="w-24 h-24 rounded-xl object-cover" alt="">
            @endif
            <p class="text-2xl uppercase tracking-widest opacity-60">{{ setting('general.title') }}</p>
        </div>
        <h1 class="text-7xl font-bold leading-tight">{{ $post->title }}</h1>
        <p class="text-2xl opacity-70">{{ $post->published_at?->format('F j, Y') }}</p>
    </div>
</x-og-image>
```

`page.blade.php`: same structure minus the date line. **Adjust every accessor** (`featuredImage()`, `setting('general.title')`, `published_at`) to what `stubs/theme/post.blade.php` and the models actually expose — read them first; do not invent methods. If the vendor component requires the template file itself to be the inner content when invoked via `view=` (i.e. no `<x-og-image>` wrapper inside the mapped view — check Task 1's findings and the vendor docs' `view=` semantics), strip the wrapper accordingly and note it in the component's view (Task 3) instead.

- [ ] **Step 2: Add the component to the example layout**

In `stubs/theme/components/layouts/main/index.blade.php`, add `<x-franken-og-image />` just before `</body>`.

- [ ] **Step 3: Filament helper text**

On the `SpatieMediaLibraryFileUpload::make('seo_og_image')` field in `HasSeoFields.php`, add:

```php
->helperText(function ($record) {
    if ($record && \FrankenCms\OgImage\OgImageFeature::templateFor($record)) {
        return 'An OG image template is active for this content type — this upload overrides nothing unless the template is removed, but is used as a fallback.';
    }

    return 'Recommended size 1200×630. Used for social sharing previews.';
})
```

Reword to match precedence reality: template wins over the upload (per the resolution order). Keep existing helper text if the field already has some — merge, don't clobber.

- [ ] **Step 4: Run the suite**

Run: `composer test`
Expected: PASS (stubs aren't executed by tests; Filament change is closure-only).

- [ ] **Step 5: Commit**

```bash
git add stubs/theme/og-templates stubs/theme/components/layouts/main/index.blade.php src/Filament/Resources/Concerns/HasSeoFields.php
git commit -m "feat: ship example OG templates and surface template state in the SEO tab"
```

---

### Task 6: Installer step

**Files:**
- Modify: `src/Commands/InstallCommand.php` (new optional step after Filament theme setup / near the example-theme step ~line 724)
- Modify: `src/Support/IgorMessages.php` (messages for the new step key)

**Interfaces:**
- Consumes: `$packageManager` detection and the step/message patterns already in `InstallCommand` (read 2–3 existing steps first and mirror them exactly — including how `IgorMessages` keys map to steps).

- [ ] **Step 1: Add the step**

New method `offerOgImageSetup()`, called from `handle()` in step order after the theme setup. Behavior:
1. If `OgImageFeature::isInstalled()` already → Igor acknowledges and returns.
2. `confirm()` (Laravel Prompts, matching existing style): "Install spatie/laravel-og-image for automatic OG image generation? (Recommended — needs Chrome/Node on the server, or Cloudflare credentials)".
3. On yes: run `composer require spatie/laravel-og-image` via the same `exec()`/process pattern the command already uses for package installs (NOT `callSilently` on artisan commands — see the memory note about interactive commands), then `vendor:publish` the `og-image-config` tag.
4. Print (via IgorMessages) the follow-ups: map templates in `config/franken-cms.php` → `og_image.templates`, add `<x-franken-og-image />` to the theme layout (already present if they installed the example theme), and for Chrome-less hosts set `CLOUDFLARE_API_TOKEN` + `CLOUDFLARE_ACCOUNT_ID`.
5. On decline: one-liner that manual uploads keep working and the feature can be added later.

Add matching Igor/Doctor dialogue entries in `IgorMessages` under a new step key, following the existing organization.

- [ ] **Step 2: Test the installer changes**

If `InstallCommand` has existing tests, extend them for the new step (declining path at minimum — assert the step is offered and skippable). If it has none, run the suite and do a dry review only — do not build an installer test harness for this task.

Run: `composer test`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add src/Commands/InstallCommand.php src/Support/IgorMessages.php
git commit -m "feat: offer OG image generation setup during install"
```

---

### Task 7: Docs, analysis, final verification

**Files:**
- Modify: `README.md`
- Modify: anything PHPStan/Pint flags in branch-touched files

- [ ] **Step 1: README section**

Add an "Open Graph Images" section (near the AI section): recommended setup `composer require spatie/laravel-og-image`; map templates in `config/franken-cms.php` (`og_image.templates`, keyed by post type); drop `<x-franken-og-image />` into the theme layout; resolution order (template → per-post upload → site default); server needs Chrome/Node **or** set `CLOUDFLARE_API_TOKEN`/`CLOUDFLARE_ACCOUNT_ID` for Cloudflare Browser Rendering; hand-coded non-CMS pages can use `<x-og-image>` directly per Spatie's docs; without the package, manual uploads and the site default keep working exactly as before. Preview tip: append `?ogimage` to any page URL.

- [ ] **Step 2: Analysis + format + suite**

Run: `vendor/bin/phpstan analyse --memory-limit=1G` — zero NEW errors in branch-touched files (src/OgImage/, src/View/Components/OgImage.php, AddSeoDefaults.php, HasSeoFields.php, InstallCommand.php, FrankenCmsServiceProvider.php); ~238 pre-existing elsewhere are out of scope.
Run: `composer format` then `composer test` — all green.

- [ ] **Step 3: Commit and push**

```bash
git add -A
git commit -m "docs: document OG image generation setup"
git push -u origin feature/og-image-integration
```

- [ ] **Step 4: Report**

Note for the user: smoke-testing generation end-to-end needs the test app (`~/Sites/frankecms`) with `composer require spatie/laravel-og-image` + Chrome/Node (Herd machines have Node; puppeteer's Chrome may need `npx puppeteer browsers install chrome`) or Cloudflare credentials. Do not modify the test app unasked.
