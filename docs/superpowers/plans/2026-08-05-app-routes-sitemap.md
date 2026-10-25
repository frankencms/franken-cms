# App Routes Sitemap Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auto-generate `sitemap-routes.xml` covering the host app's own static public routes, with `->sitemap()` / `->skipSitemap()` macro overrides and an on-by-default settings toggle.

**Architecture:** A new `RouteSitemapService` walks `Route::getRoutes()` applying conservative filters (static GET web routes; skip vendor/package/CMS-page/gated routes). Macros store per-route metadata in the route's action array (survives `route:cache`, Octane-safe, no registry). A new `SitemapController::routes()` action serves the XML built with the in-house `FrankenCms\Sitemap` classes; the sitemap index gains an entry when the toggle is on.

**Tech Stack:** Laravel 13 routing, Spatie Laravel Settings, Filament v5 forms, Pest v4. No new dependencies.

**Spec:** `docs/superpowers/specs/2026-08-05-route-sitemap-design.md`

## Global Constraints

- PHP 8.4, `declare(strict_types=1);` at the top of every new src file.
- Pint style: aligned `=>` operators, `new Foo` not `new Foo()`, class braces on next line, control-structure braces same line. Run `composer format` before each commit.
- PHPStan: `composer analyse` currently fails with ~234 PRE-EXISTING errors (stale baseline). The pass bar is: **no NEW errors mentioning your files**. Check with `composer analyse 2>&1 | grep -A6 "src/Sitemap\|RouteSitemap\|PageRouteService\|FrankenCmsServiceProvider"`.
- `composer test` currently exits 1 due to ONE pre-existing risky test (`BlogPostWizardTest` prints streaming output). The pass bar is: **0 failed** (risky ≠ failed).
- No `new static` (PHPStan flags it) — use `new self` with `self` return types on `create()`-style factories.
- Never cache the routes sitemap (spec decision: route tables change at deploy; no invalidation hook exists).
- `excluded_paths` always wins — evaluated last, even over `->sitemap()` force-include.
- Tests: Pest with `describe`/`test` blocks. All test files auto-use `FrankenCms\Tests\TestCase` + `RefreshDatabase` via `tests/Pest.php`.

---

### Task 1: Extract shared UrlExclusionMatcher

The wildcard matcher is currently private logic inside `SitemapService::isExcluded()` (`src/Services/SitemapService.php:315-330`). Both `SitemapService` and the new `RouteSitemapService` need identical matching, so extract it.

**Files:**
- Create: `src/Sitemap/UrlExclusionMatcher.php`
- Modify: `src/Services/SitemapService.php` (the `isExcluded()` method)
- Test: `tests/Unit/UrlExclusionMatcherTest.php`

**Interfaces:**
- Produces: `FrankenCms\Sitemap\UrlExclusionMatcher::__construct(array $patterns)` and `->matches(string $url): bool`. Patterns support `*` wildcards, matched against the FULL absolute URL. Task 6 consumes this.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/UrlExclusionMatcherTest.php`:

```php
<?php

use FrankenCms\Sitemap\UrlExclusionMatcher;

describe('UrlExclusionMatcher', function () {
    test('matches exact url', function () {
        $matcher = new UrlExclusionMatcher(['https://example.com/private']);

        expect($matcher->matches('https://example.com/private'))->toBeTrue();
        expect($matcher->matches('https://example.com/public'))->toBeFalse();
    });

    test('matches wildcard patterns', function () {
        $matcher = new UrlExclusionMatcher(['*/admin-*']);

        expect($matcher->matches('https://example.com/admin-dashboard'))->toBeTrue();
        expect($matcher->matches('https://example.com/about'))->toBeFalse();
    });

    test('does not partially match without wildcards', function () {
        $matcher = new UrlExclusionMatcher(['https://example.com/private']);

        expect($matcher->matches('https://example.com/private-eye'))->toBeFalse();
    });

    test('returns false with no patterns', function () {
        $matcher = new UrlExclusionMatcher([]);

        expect($matcher->matches('https://example.com/anything'))->toBeFalse();
    });

    test('escapes regex special characters in patterns', function () {
        $matcher = new UrlExclusionMatcher(['https://example.com/page?tab=1']);

        expect($matcher->matches('https://example.com/page?tab=1'))->toBeTrue();
        expect($matcher->matches('https://example.com/pageXtab=1'))->toBeFalse();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/UrlExclusionMatcherTest.php`
Expected: FAIL — `Class "FrankenCms\Sitemap\UrlExclusionMatcher" not found`

- [ ] **Step 3: Write minimal implementation**

Create `src/Sitemap/UrlExclusionMatcher.php`:

```php
<?php

declare(strict_types=1);

namespace FrankenCms\Sitemap;

class UrlExclusionMatcher
{
    /**
     * @param array<int, string> $patterns Wildcard patterns matched against full URLs
     */
    public function __construct(
        protected array $patterns
    ) {}

    public function matches(string $url): bool
    {
        foreach ($this->patterns as $pattern) {
            // Support wildcards - escape everything except *, then replace * with .*
            $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));

            if (preg_match('/^' . $regex . '$/', $url)) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/UrlExclusionMatcherTest.php`
Expected: PASS (5 tests)

- [ ] **Step 5: Refactor SitemapService::isExcluded to use the matcher**

In `src/Services/SitemapService.php`, add the import `use FrankenCms\Sitemap\UrlExclusionMatcher;` and replace the `isExcluded()` method body:

```php
    /**
     * Check if a post URL should be excluded from sitemap
     */
    protected function isExcluded(Post $post): bool
    {
        return (new UrlExclusionMatcher($this->settings->excluded_paths))
            ->matches($this->getPostUrl($post));
    }
```

(The old body looped `$this->settings->excluded_paths` doing `preg_quote` + `str_replace('\*', '.*', ...)` inline — delete it entirely.)

- [ ] **Step 6: Run the sitemap suites to verify the refactor holds**

Run: `vendor/bin/pest tests/Unit/SitemapServiceTest.php tests/Unit/UrlExclusionMatcherTest.php`
Expected: PASS (the `Excluded paths` describe block proves equivalence)

- [ ] **Step 7: Format and commit**

```bash
composer format
git add src/Sitemap/UrlExclusionMatcher.php src/Services/SitemapService.php tests/Unit/UrlExclusionMatcherTest.php
git commit -m "refactor: extract UrlExclusionMatcher from SitemapService"
```

---

### Task 2: Expose registered page route names from PageRouteService

CMS pages with a `route_name` are registered as named closure routes by `PageRouteService::registerPageRoutes()` (`src/Services/PageRouteService.php:18`). Those pages already appear in `sitemap-pages.xml`, so route discovery (Task 5) must skip them by name. The names are user-chosen, so the service must expose them.

**Files:**
- Modify: `src/Services/PageRouteService.php`
- Test: `tests/Unit/PageRouteServiceTest.php` (exists — append a describe block)

**Interfaces:**
- Consumes: existing `protected getCachedPages(): array` returning `[['id' => .., 'route_name' => .., 'path' => ..], ...]`, cached under key `franken_cms_page_routes`.
- Produces: `public function getRegisteredRouteNames(): array` — flat `array<int, string>` of route names. Task 5 consumes this.

- [ ] **Step 1: Write the failing test**

Append to `tests/Unit/PageRouteServiceTest.php` (match the file's existing setup conventions — it already creates posts via factories; note pages need a `User` with id 1 if the existing `beforeEach` doesn't create one, copy what the file already does):

```php
describe('Registered route names', function () {
    test('returns route names of published pages that have one', function () {
        Cache::forget('franken_cms_page_routes');

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'landing',
            'route_name'  => 'my.landing',
        ]);

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'no-name-page',
            'route_name'  => null,
        ]);

        $names = app(PageRouteService::class)->getRegisteredRouteNames();

        expect($names)->toBe(['my.landing']);
    });

    test('returns empty array when no pages have route names', function () {
        Cache::forget('franken_cms_page_routes');

        $names = app(PageRouteService::class)->getRegisteredRouteNames();

        expect($names)->toBe([]);
    });
});
```

Add any missing imports at the top of the test file: `use FrankenCms\Enums\PostStatus;`, `use FrankenCms\Models\Post;`, `use FrankenCms\Services\PageRouteService;`, `use Illuminate\Support\Facades\Cache;`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/PageRouteServiceTest.php --filter="Registered route names"`
Expected: FAIL — `Call to undefined method ... getRegisteredRouteNames()`

- [ ] **Step 3: Write minimal implementation**

Add to `src/Services/PageRouteService.php` (after `registerPageRoutes()`, before `getCachedPages()`):

```php
    /**
     * Route names of published pages registered as named routes
     *
     * @return array<int, string>
     */
    public function getRegisteredRouteNames(): array
    {
        return array_column($this->getCachedPages(), 'route_name');
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/PageRouteServiceTest.php`
Expected: PASS (whole file — including pre-existing tests)

- [ ] **Step 5: Format and commit**

```bash
composer format
git add src/Services/PageRouteService.php tests/Unit/PageRouteServiceTest.php
git commit -m "feat: expose registered page route names from PageRouteService"
```

---

### Task 3: Add include_app_routes setting, migration, and Filament toggle

**Files:**
- Modify: `src/Settings/SitemapSettings.php`
- Create: `database/migrations/25_add_sitemap_include_app_routes.php`
- Modify: `src/FrankenCmsServiceProvider.php` (the `hasMigrations([...])` list, ends at `'24_rename_ai_engine_settings',`)
- Modify: `src/SettingsTabs/SitemapSettingsTabProvider.php`
- Modify: `tests/Unit/SitemapServiceTest.php` (the `createSettingsMock()` helper)
- Test: `tests/Unit/SitemapXmlTest.php` untouched; verification is via the settings test below + full suite

**Interfaces:**
- Produces: `SitemapSettings::$include_app_routes` (`public bool`, DB default `true`, group `franken_cms_sitemap`). Tasks 6 and 7 consume this.

- [ ] **Step 1: Write the failing test**

Append to `tests/Unit/SitemapServiceTest.php` (inside the file, as a new top-level describe; the real settings class is DB-backed via the settings migrations that run in tests):

```php
describe('Settings defaults', function () {
    test('include_app_routes defaults to true', function () {
        expect(app(SitemapSettings::class)->include_app_routes)->toBeTrue();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/SitemapServiceTest.php --filter="Settings defaults"`
Expected: FAIL — missing settings property / `MissingSettings` (property not in class or DB)

- [ ] **Step 3: Implement setting + migration + provider registration**

3a. Add to `src/Settings/SitemapSettings.php` after the `$custom_sitemaps` property:

```php
    /**
     * Automatically include the host app's own static public routes
     * as sitemap-routes.xml in the sitemap index
     */
    public bool $include_app_routes;
```

3b. Create `database/migrations/25_add_sitemap_include_app_routes.php`:

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Auto-generate sitemap-routes.xml for host app routes by default
        $this->migrator->add('franken_cms_sitemap.include_app_routes', true);
    }
};
```

3c. In `src/FrankenCmsServiceProvider.php`, in the `hasMigrations([...])` array, add after `'24_rename_ai_engine_settings',`:

```php
                '25_add_sitemap_include_app_routes',
```

3d. In `tests/Unit/SitemapServiceTest.php`, inside `createSettingsMock()`, add after `$settings->custom_sitemaps = [];`:

```php
    $settings->include_app_routes = true;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/SitemapServiceTest.php`
Expected: PASS (whole file)

- [ ] **Step 5: Add the Filament toggle**

In `src/SettingsTabs/SitemapSettingsTabProvider.php`, after the `Toggle::make('include_images')...->columnSpanFull(),` block (ends line ~93), add:

```php
                        Toggle::make('include_app_routes')
                            ->label('App Routes Sitemap')
                            ->helperText('Automatically include your application\'s own static public routes (non-CMS pages) as sitemap-routes.xml. Routes can opt out in code with ->skipSitemap().')
                            ->default(true)
                            ->columnSpanFull(),
```

- [ ] **Step 6: Run settings-related suites**

Run: `vendor/bin/pest tests/Unit/SitemapServiceTest.php tests/Feature/SettingsTabSystemTest.php tests/Unit/SettingsTabServiceTest.php`
Expected: PASS

- [ ] **Step 7: Format and commit**

```bash
composer format
git add src/Settings/SitemapSettings.php database/migrations/25_add_sitemap_include_app_routes.php src/FrankenCmsServiceProvider.php src/SettingsTabs/SitemapSettingsTabProvider.php tests/Unit/SitemapServiceTest.php
git commit -m "feat: add include_app_routes sitemap setting with Filament toggle"
```

---

### Task 4: Register sitemap route macros

**Files:**
- Modify: `src/FrankenCmsServiceProvider.php` (`packageBooted()`, starts line ~184)
- Test: `tests/Unit/RouteSitemapMacrosTest.php`

**Interfaces:**
- Produces: on `Illuminate\Routing\Route`: `->sitemap(?float $priority = null, ?string $changeFrequency = null): Route` storing `['include' => true, 'priority' => ?float, 'change_frequency' => ?string]` under action key `sitemap`; `->skipSitemap(): Route` storing `['include' => false]`. Read back via `$route->getAction('sitemap')`. Tasks 5–6 consume this exact array shape.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/RouteSitemapMacrosTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

describe('Route sitemap macros', function () {
    test('sitemap macro stores include metadata on the route action', function () {
        $route = Route::get('/macro-test', fn () => 'ok')->sitemap(0.9, 'monthly');

        expect($route->getAction('sitemap'))->toBe([
            'include'          => true,
            'priority'         => 0.9,
            'change_frequency' => 'monthly',
        ]);
    });

    test('sitemap macro defaults to null metadata', function () {
        $route = Route::get('/macro-default-test', fn () => 'ok')->sitemap();

        expect($route->getAction('sitemap'))->toBe([
            'include'          => true,
            'priority'         => null,
            'change_frequency' => null,
        ]);
    });

    test('skipSitemap macro stores exclusion on the route action', function () {
        $route = Route::get('/macro-skip-test', fn () => 'ok')->skipSitemap();

        expect($route->getAction('sitemap'))->toBe(['include' => false]);
    });

    test('macros are chainable', function () {
        $route = Route::get('/macro-chain-test', fn () => 'ok')->sitemap()->name('chain.test');

        expect($route->getName())->toBe('chain.test');
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/RouteSitemapMacrosTest.php`
Expected: FAIL — `Method Illuminate\Routing\Route::sitemap does not exist`

- [ ] **Step 3: Write minimal implementation**

In `src/FrankenCmsServiceProvider.php`:

Add import: `use Illuminate\Routing\Route as RoutingRoute;`

At the top of `packageBooted()` (before the `$this->app->singleton(CurrentPageService::class);` line), add:

```php
        // Per-route sitemap overrides, stored in the action array: survives
        // route:cache and never leaks into parameter binding like defaults()
        RoutingRoute::macro('sitemap', function (?float $priority = null, ?string $changeFrequency = null) {
            /** @var RoutingRoute $this */
            $this->setAction(array_merge($this->getAction(), [
                'sitemap' => [
                    'include'          => true,
                    'priority'         => $priority,
                    'change_frequency' => $changeFrequency,
                ],
            ]));

            return $this;
        });

        RoutingRoute::macro('skipSitemap', function () {
            /** @var RoutingRoute $this */
            $this->setAction(array_merge($this->getAction(), [
                'sitemap' => ['include' => false],
            ]));

            return $this;
        });
```

(The `/** @var RoutingRoute $this */` annotations declare the macro's runtime binding — without them PHPStan types `$this` as the service provider.)

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/RouteSitemapMacrosTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Format and commit**

```bash
composer format
git add src/FrankenCmsServiceProvider.php tests/Unit/RouteSitemapMacrosTest.php
git commit -m "feat: add sitemap/skipSitemap route macros"
```

---

### Task 5: RouteSitemapService — structural and heuristic discovery

**Files:**
- Create: `src/Services/RouteSitemapService.php`
- Modify: `src/FrankenCmsServiceProvider.php` (service registration, next to `$this->app->singleton(SitemapService::class);` at line ~159)
- Test: `tests/Unit/RouteSitemapServiceTest.php`

**Interfaces:**
- Consumes: `PageRouteService::getRegisteredRouteNames(): array` (Task 2); macro action shape (Task 4); `SitemapSettings::$include_app_routes` (Task 3); `FrankenCms\Sitemap\{Sitemap, Url}` and `UrlExclusionMatcher` (Task 1 / existing).
- Produces: `RouteSitemapService::__construct(SitemapSettings $settings, Router $router, PageRouteService $pageRouteService)`, `->isEnabled(): bool`, `->generate(): FrankenCms\Sitemap\Sitemap`. Task 7 consumes these.

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/RouteSitemapServiceTest.php`. IMPORTANT: the helper is named `createRouteSitemapSettingsMock` — `createSettingsMock` already exists globally in `SitemapServiceTest.php` and Pest test-file functions share one namespace.

```php
<?php

use FrankenCms\Services\PageRouteService;
use FrankenCms\Services\RouteSitemapService;
use FrankenCms\Settings\SitemapSettings;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelSettings\Settings;

beforeEach(function () {
    // Posts require an author with id 1 (same setup as SitemapServiceTest)
    FrankenCms\Tests\Support\User::create([
        'id'       => 1,
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

function createRouteSitemapSettingsMock(): SitemapSettings
{
    $settings = Mockery::mock(SitemapSettings::class)->makePartial();

    $reflection = new ReflectionProperty(Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    $settings->enabled = true;
    $settings->default_change_frequency = 'weekly';
    $settings->default_priority = 0.5;
    $settings->max_urls_per_sitemap = 50000;
    $settings->excluded_paths = [];
    $settings->include_images = true;
    $settings->custom_sitemaps = [];
    $settings->include_app_routes = true;

    return $settings;
}

function routeSitemapService(?SitemapSettings $settings = null): RouteSitemapService
{
    return new RouteSitemapService(
        $settings ?? createRouteSitemapSettingsMock(),
        app('router'),
        app(PageRouteService::class)
    );
}

describe('Structural filters', function () {
    test('includes static GET web routes', function () {
        Route::middleware('web')->get('pricing', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->toContain(url('pricing'));
    });

    test('includes unnamed routes', function () {
        Route::middleware('web')->get('unnamed-page', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->toContain(url('unnamed-page'));
    });

    test('includes Route::view routes', function () {
        Route::view('view-page', 'welcome')->middleware('web');

        expect(routeSitemapService()->generate()->render())->toContain(url('view-page'));
    });

    test('excludes parameterized routes', function () {
        Route::middleware('web')->get('products/{id}', fn ($id) => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('products');
    });

    test('excludes non-GET routes', function () {
        Route::middleware('web')->post('contact-submit', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('contact-submit');
    });

    test('excludes routes without web middleware', function () {
        Route::get('api-like-route', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('api-like-route');
    });

    test('excludes domain-restricted routes', function () {
        Route::middleware('web')->domain('sub.example.com')->get('domain-page', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('domain-page');
    });
});

describe('Heuristic filters', function () {
    test('excludes routes behind auth middleware', function () {
        Route::middleware(['web', 'auth'])->get('dashboard-page', fn () => 'ok');
        Route::middleware(['web', 'auth:sanctum'])->get('sanctum-page', fn () => 'ok');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('dashboard-page');
        expect($xml)->not->toContain('sanctum-page');
    });

    test('excludes guest, verified, signed, and password.confirm routes', function () {
        Route::middleware(['web', 'guest'])->get('login-page', fn () => 'ok');
        Route::middleware(['web', 'verified'])->get('verified-page', fn () => 'ok');
        Route::middleware(['web', 'signed'])->get('signed-page', fn () => 'ok');
        Route::middleware(['web', 'password.confirm'])->get('confirm-page', fn () => 'ok');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('login-page');
        expect($xml)->not->toContain('verified-page');
        expect($xml)->not->toContain('signed-page');
        expect($xml)->not->toContain('confirm-page');
    });

    test('excludes vendor routes by name prefix', function () {
        Route::middleware('web')->get('vendor-a', fn () => 'ok')->name('filament.admin.pages.dashboard');
        Route::middleware('web')->get('vendor-b', fn () => 'ok')->name('livewire.update');
        Route::middleware('web')->get('vendor-c', fn () => 'ok')->name('horizon.index');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('vendor-a');
        expect($xml)->not->toContain('vendor-b');
        expect($xml)->not->toContain('vendor-c');
    });

    test('excludes package routes', function () {
        // robots.txt, feed, feed/atom, sitemap*.xml are registered by the
        // package's own routes/web.php which is loaded in tests
        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('robots.txt');
        expect($xml)->not->toContain('sitemap.xml');
        expect($xml)->not->toContain(url('feed'));
    });

    test('excludes CMS page routes registered by PageRouteService', function () {
        Illuminate\Support\Facades\Cache::forget('franken_cms_page_routes');

        FrankenCms\Models\Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => FrankenCms\Enums\PostStatus::PUBLISH,
            'post_slug'   => 'landing',
            'route_name'  => 'my.landing',
        ]);

        Route::middleware('web')->get('landing', fn () => 'ok')->name('my.landing');

        expect(routeSitemapService()->generate()->render())->not->toContain(url('landing'));
    });

    test('excludes redirect routes', function () {
        Route::redirect('old-path', 'new-path')->middleware('web');

        expect(routeSitemapService()->generate()->render())->not->toContain('old-path');
    });
});
```


- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/RouteSitemapServiceTest.php`
Expected: FAIL — `Class "FrankenCms\Services\RouteSitemapService" not found`

- [ ] **Step 3: Write the implementation**

Create `src/Services/RouteSitemapService.php`:

```php
<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Settings\SitemapSettings;
use FrankenCms\Sitemap\Sitemap;
use FrankenCms\Sitemap\Url;
use FrankenCms\Sitemap\UrlExclusionMatcher;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class RouteSitemapService
{
    /**
     * Package route names that must never appear in the routes sitemap
     */
    protected const PACKAGE_ROUTE_NAMES = ['robots', 'sitemap'];

    /**
     * Package route name prefixes that must never appear in the routes sitemap
     */
    protected const PACKAGE_ROUTE_NAME_PREFIXES = ['feed.', 'sitemap.'];

    /**
     * Well-known vendor route name prefixes
     */
    protected const VENDOR_ROUTE_NAME_PREFIXES = [
        'filament.',
        'livewire.',
        'debugbar.',
        'horizon.',
        'telescope.',
        'pulse.',
        'ignition.',
    ];

    /**
     * Middleware that marks a route as gated (not publicly indexable)
     */
    protected const GATED_MIDDLEWARE = ['verified', 'signed', 'password.confirm', 'guest'];

    public function __construct(
        protected SitemapSettings $settings,
        protected Router $router,
        protected PageRouteService $pageRouteService
    ) {}

    /**
     * Check if the app routes sitemap is enabled
     */
    public function isEnabled(): bool
    {
        return $this->settings->enabled && $this->settings->include_app_routes;
    }

    /**
     * Build the sitemap for the host app's own static public routes.
     * Deliberately uncached: generation is an in-memory route-table walk,
     * and routes change at deploy time with no invalidation hook.
     */
    public function generate(): Sitemap
    {
        $sitemap = Sitemap::create();
        $matcher = new UrlExclusionMatcher($this->settings->excluded_paths);
        $pageRouteNames = $this->pageRouteService->getRegisteredRouteNames();

        foreach ($this->router->getRoutes() as $route) {
            if (! $this->shouldInclude($route, $pageRouteNames)) {
                continue;
            }

            $url = url($route->uri());

            // excluded_paths always wins, even over a ->sitemap() force-include
            if ($matcher->matches($url)) {
                continue;
            }

            $sitemap->add($this->createUrlForRoute($route, $url));
        }

        return $sitemap;
    }

    /**
     * @param array<int, string> $pageRouteNames
     */
    protected function shouldInclude(Route $route, array $pageRouteNames): bool
    {
        // Structural requirements - never overridable
        if (! in_array('GET', $route->methods(), true)) {
            return false;
        }

        if (str_contains($route->uri(), '{')) {
            return false;
        }

        if ($route->getDomain() !== null) {
            return false;
        }

        $middleware = $route->gatherMiddleware();

        if (! in_array('web', $middleware, true)) {
            return false;
        }

        // Macro overrides beat the heuristics below
        $meta = $route->getAction('sitemap');
        if (is_array($meta)) {
            return ($meta['include'] ?? null) === true;
        }

        return ! $this->isPackageRoute($route, $pageRouteNames)
            && ! $this->isVendorRoute($route)
            && ! $this->isGated($middleware)
            && ! $this->isRedirect($route);
    }

    /**
     * @param array<int, string> $pageRouteNames
     */
    protected function isPackageRoute(Route $route, array $pageRouteNames): bool
    {
        $name = $route->getName();

        if ($name !== null) {
            if (in_array($name, self::PACKAGE_ROUTE_NAMES, true)) {
                return true;
            }

            foreach (self::PACKAGE_ROUTE_NAME_PREFIXES as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    return true;
                }
            }

            // CMS pages registered as named routes are already in sitemap-pages.xml
            if (in_array($name, $pageRouteNames, true)) {
                return true;
            }
        }

        return str_starts_with(ltrim($route->getActionName(), '\\'), 'FrankenCms\\');
    }

    protected function isVendorRoute(Route $route): bool
    {
        $name = $route->getName();

        if ($name === null) {
            return false;
        }

        foreach (self::VENDOR_ROUTE_NAME_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $middleware
     */
    protected function isGated(array $middleware): bool
    {
        foreach ($middleware as $item) {
            if (! is_string($item)) {
                continue;
            }

            if ($item === 'auth' || str_starts_with($item, 'auth:')) {
                return true;
            }

            if (in_array($item, self::GATED_MIDDLEWARE, true)) {
                return true;
            }
        }

        return false;
    }

    protected function isRedirect(Route $route): bool
    {
        return str_starts_with(ltrim($route->getActionName(), '\\'), RedirectController::class);
    }

    protected function createUrlForRoute(Route $route, string $url): Url
    {
        $meta = $route->getAction('sitemap');
        $meta = is_array($meta) ? $meta : [];

        // No lastmod: the last-change time of a coded page is unknowable
        return Url::create($url)
            ->setChangeFrequency($meta['change_frequency'] ?? $this->settings->default_change_frequency)
            ->setPriority($meta['priority'] ?? $this->settings->default_priority);
    }
}
```

Register it in `src/FrankenCmsServiceProvider.php` — after `$this->app->singleton(SitemapService::class);` add:

```php
        $this->app->singleton(RouteSitemapService::class);
```

Add the import `use FrankenCms\Services\RouteSitemapService;` if the file imports services individually (follow the file's existing import pattern for `SitemapService`).

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/RouteSitemapServiceTest.php`
Expected: PASS (all describe blocks)

- [ ] **Step 5: Format and commit**

```bash
composer format
git add src/Services/RouteSitemapService.php src/FrankenCmsServiceProvider.php tests/Unit/RouteSitemapServiceTest.php
git commit -m "feat: add RouteSitemapService with route auto-discovery"
```

---

### Task 6: RouteSitemapService — macro overrides, exclusions, metadata

The implementation already landed in Task 5 (`shouldInclude` consults the macro action, `createUrlForRoute` applies metadata, the matcher runs last). This task proves those behaviors with tests — if any fail, fix the service until green.

**Files:**
- Modify: `tests/Unit/RouteSitemapServiceTest.php` (append describe blocks)
- Possibly modify: `src/Services/RouteSitemapService.php` (only if a test exposes a gap)

**Interfaces:**
- Consumes: everything produced in Tasks 4–5.
- Produces: verified behavior contract for Task 7.

- [ ] **Step 1: Write the tests**

Append to `tests/Unit/RouteSitemapServiceTest.php`:

```php
describe('Macro overrides', function () {
    test('skipSitemap excludes an otherwise eligible route', function () {
        Route::middleware('web')->get('hidden-page', fn () => 'ok')->skipSitemap();

        expect(routeSitemapService()->generate()->render())->not->toContain('hidden-page');
    });

    test('sitemap macro force-includes a gated route', function () {
        Route::middleware(['web', 'auth'])->get('members-welcome', fn () => 'ok')->sitemap();

        expect(routeSitemapService()->generate()->render())->toContain(url('members-welcome'));
    });

    test('sitemap macro never overrides structural requirements', function () {
        Route::middleware('web')->get('items/{id}', fn ($id) => 'ok')->sitemap();

        expect(routeSitemapService()->generate()->render())->not->toContain('items');
    });

    test('sitemap macro metadata overrides settings defaults', function () {
        Route::middleware('web')->get('special-page', fn () => 'ok')->sitemap(0.9, 'monthly');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->toContain('<priority>0.9</priority>');
        expect($xml)->toContain('<changefreq>monthly</changefreq>');
    });

    test('excluded_paths beats a sitemap force-include', function () {
        Route::middleware('web')->get('force-included', fn () => 'ok')->sitemap();

        $settings = createRouteSitemapSettingsMock();
        $settings->excluded_paths = ['*/force-included'];

        expect(routeSitemapService($settings)->generate()->render())->not->toContain('force-included');
    });
});

describe('Metadata defaults', function () {
    test('routes get priority and changefreq from settings', function () {
        Route::middleware('web')->get('defaults-page', fn () => 'ok');

        $settings = createRouteSitemapSettingsMock();
        $settings->default_priority = 0.3;
        $settings->default_change_frequency = 'yearly';

        $xml = routeSitemapService($settings)->generate()->render();

        expect($xml)->toContain('<priority>0.3</priority>');
        expect($xml)->toContain('<changefreq>yearly</changefreq>');
    });

    test('route urls have no lastmod tag', function () {
        Route::middleware('web')->get('no-lastmod-page', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('<lastmod>');
    });
});

describe('Enabled state', function () {
    test('enabled only when both toggles are on', function () {
        $settings = createRouteSitemapSettingsMock();
        expect(routeSitemapService($settings)->isEnabled())->toBeTrue();

        $settings = createRouteSitemapSettingsMock();
        $settings->enabled = false;
        expect(routeSitemapService($settings)->isEnabled())->toBeFalse();

        $settings = createRouteSitemapSettingsMock();
        $settings->include_app_routes = false;
        expect(routeSitemapService($settings)->isEnabled())->toBeFalse();
    });
});
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/pest tests/Unit/RouteSitemapServiceTest.php`
Expected: PASS. If any test fails, fix `RouteSitemapService` (not the test) until green — the spec behaviors here are: macro include wins over heuristics but not structure; `excluded_paths` runs last and always wins; metadata falls back per-field to settings defaults; no lastmod on route URLs.

- [ ] **Step 3: Format and commit**

```bash
composer format
git add tests/Unit/RouteSitemapServiceTest.php src/Services/RouteSitemapService.php
git commit -m "test: cover macro overrides and metadata for route sitemap"
```

---

### Task 7: Endpoint, route registration, and sitemap index entry

**Files:**
- Modify: `src/Http/Controllers/SitemapController.php`
- Modify: `routes/web.php` (after the `sitemap-posts.xml` line)
- Modify: `src/Services/SitemapService.php` (`generate()`)
- Modify: `tests/Unit/SitemapServiceTest.php` (index describe block)
- Test: `tests/Feature/RouteSitemapTest.php`

**Interfaces:**
- Consumes: `RouteSitemapService::isEnabled()` / `->generate()` (Tasks 5–6), `SitemapSettings::$include_app_routes` (Task 3).
- Produces: public URL `sitemap-routes.xml` (route name `sitemap.routes`); index entry in `sitemap.xml`.

- [ ] **Step 1: Write the failing feature tests**

Create `tests/Feature/RouteSitemapTest.php`:

```php
<?php

use FrankenCms\Settings\SitemapSettings;
use Illuminate\Support\Facades\Route;

describe('sitemap-routes.xml endpoint', function () {
    test('serves discovered app routes as xml', function () {
        Route::middleware('web')->get('pricing', fn () => 'ok');

        $response = $this->get('/sitemap-routes.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        expect($response->getContent())->toContain(url('pricing'));
        expect($response->getContent())->toContain('<urlset');
    });

    test('serves a valid empty urlset when no routes qualify', function () {
        $response = $this->get('/sitemap-routes.xml');

        $response->assertOk();
        expect($response->getContent())->toContain('<urlset');
    });

    test('returns 404 when app routes sitemap is disabled', function () {
        $settings = app(SitemapSettings::class);
        $settings->include_app_routes = false;
        $settings->save();

        $this->get('/sitemap-routes.xml')->assertNotFound();
    });

    test('returns 404 when the sitemap system is disabled', function () {
        $settings = app(SitemapSettings::class);
        $settings->enabled = false;
        $settings->save();

        $this->get('/sitemap-routes.xml')->assertNotFound();
    });
});

describe('sitemap index entry', function () {
    test('index lists sitemap-routes.xml when enabled', function () {
        $this->get('/sitemap.xml')->assertOk()->assertSee('sitemap-routes.xml');
    });

    test('index omits sitemap-routes.xml when disabled', function () {
        $settings = app(SitemapSettings::class);
        $settings->include_app_routes = false;
        $settings->save();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        expect($response->getContent())->not->toContain('sitemap-routes.xml');
    });
});
```

(`$settings->save()` fires `SettingsSaved`, which `ClearSitemapCacheListener` handles by clearing the forever-cached index — that's what makes the toggle tests deterministic.)

Also append to the `Sitemap index generation` describe block in `tests/Unit/SitemapServiceTest.php`:

```php
    test('includes app routes sitemap in index when enabled', function () {
        expect($this->service->render())->toContain('sitemap-routes.xml');
    });

    test('omits app routes sitemap from index when disabled', function () {
        $this->settings->include_app_routes = false;

        $service = new SitemapService($this->settings);

        expect($service->render())->not->toContain('sitemap-routes.xml');
    });
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/RouteSitemapTest.php tests/Unit/SitemapServiceTest.php`
Expected: FAIL — 404s on `/sitemap-routes.xml` (route missing) and missing index entries. NOTE: the "returns 404 when disabled" tests will PASS at this point for the wrong reason (route doesn't exist) — that's fine; the suite as a whole is red.

- [ ] **Step 3: Implement**

3a. `src/Http/Controllers/SitemapController.php` — add import `use FrankenCms\Services\RouteSitemapService;`, extend the constructor, and add the action:

```php
    public function __construct(
        protected SitemapService $sitemapService,
        protected RouteSitemapService $routeSitemapService
    ) {}
```

```php
    /**
     * Display the sitemap of the host app's own static routes
     */
    public function routes(): Response
    {
        if (! $this->routeSitemapService->isEnabled()) {
            abort(404);
        }

        return response($this->routeSitemapService->generate()->render())
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
```

3b. `routes/web.php` — after the `sitemap-posts.xml` line (line 22), add:

```php
    Route::get('sitemap-routes.xml', [SitemapController::class, 'routes'])->name('sitemap.routes');
```

3c. `src/Services/SitemapService.php` — in `generate()`, after the `// Always add posts sitemap` block and before the custom sitemaps loop, add:

```php
        // Add app routes sitemap
        if ($this->settings->include_app_routes) {
            $sitemapIndex->add(url('sitemap-routes.xml'));
        }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/RouteSitemapTest.php tests/Unit/SitemapServiceTest.php tests/Unit/RouteSitemapServiceTest.php`
Expected: PASS. (The Task 5 test `excludes package routes` also proves the new `sitemap.routes` route doesn't include itself — it's caught by the `sitemap.` name prefix.)

- [ ] **Step 5: Run the FULL suite and static analysis**

Run: `composer test`
Expected: 0 failed (1 pre-existing risky test in BlogPostWizardTest is OK)

Run: `composer analyse 2>&1 | grep -B2 -A6 "RouteSitemap\|src/Sitemap\|SitemapController\|SitemapService"`
Expected: no output (no new errors in touched files)

- [ ] **Step 6: Format and commit**

```bash
composer format
git add src/Http/Controllers/SitemapController.php routes/web.php src/Services/SitemapService.php tests/Feature/RouteSitemapTest.php tests/Unit/SitemapServiceTest.php
git commit -m "feat: serve sitemap-routes.xml and list it in the sitemap index"
```

---

### Task 8: README documentation

**Files:**
- Modify: `README.md` (insert a new `## App Routes Sitemap` section between `## Open Graph Images` and `## Testing`, i.e. before line ~178)

**Interfaces:**
- Consumes: final behavior from Tasks 3–7. Nothing consumes this.

- [ ] **Step 1: Write the section**

Insert into `README.md` before the `## Testing` heading:

```markdown
## App Routes Sitemap

FrankenCMS automatically generates sitemaps for CMS content (`sitemap-pages.xml`, `sitemap-posts.xml`). It can also generate `sitemap-routes.xml` covering your application's **own routes** — the static pages you define in `routes/web.php` outside the CMS.

Enabled by default; toggle it under **Settings → Sitemap → App Routes Sitemap**.

### What gets included

A route is included automatically when it is a static `GET` route (no URI parameters), uses the `web` middleware, has no domain restriction, and is not:

- a vendor route (Filament, Livewire, Horizon, Telescope, Debugbar, Pulse)
- gated behind `auth`, `guest`, `verified`, `signed`, or `password.confirm` middleware
- a `Route::redirect()` (the target belongs in the sitemap, not the redirect)
- a FrankenCMS route or a CMS page route (those are already in the content sitemaps)

### Per-route overrides

```php
// Force-include a route the heuristics would skip, optionally with metadata
Route::middleware(['web', 'auth'])
    ->get('/members/welcome', WelcomeController::class)
    ->sitemap(priority: 0.9, changeFrequency: 'monthly');

// Exclude a route the heuristics would include
Route::view('/internal-demo', 'demo')->skipSitemap();
```

Allowed `changeFrequency` values: `always`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`, `never`. Priority is `0.0`–`1.0`.

Routes without overrides use the sitemap settings' default priority and change frequency. The **Excluded Paths** setting always wins — a URL matching an excluded pattern never appears, even with a `->sitemap()` force-include. Routes with URI parameters can't be enumerated; point **Custom Sitemaps** at a hand-built sitemap for those.
```

- [ ] **Step 2: Verify the suite still passes (README-only change, sanity check)**

Run: `vendor/bin/pest tests/Feature/RouteSitemapTest.php`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add README.md
git commit -m "docs: document app routes sitemap and route macros"
```
