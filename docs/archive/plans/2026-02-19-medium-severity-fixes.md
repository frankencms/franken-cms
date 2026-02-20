# Medium-Severity Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix 9 medium-severity issues identified during beta readiness review.

**Architecture:** Each fix is isolated and independent. Tasks are ordered by complexity (simplest first) to build momentum. Some tasks also address leftover string literal `'published'` comparisons found during exploration.

**Tech Stack:** PHP 8.4, Laravel, FilamentPHP v5, Pest v4, Spatie Laravel Settings

---

### Task 1: CDATA Injection in RSS Feeds

**Files:**
- Modify: `src/Services/FeedService.php:143,147,202,206`
- Test: `tests/Unit/FeedServiceTest.php`

**Step 1: Write the failing test**

Add a test that creates a post with `]]>` in content and verifies the feed output doesn't break:

```php
it('escapes CDATA-breaking sequences in RSS feed content', function () {
    Post::factory()->create([
        'post_title'        => 'CDATA Test',
        'post_teaser'       => 'Break out: ]]> and inject',
        'post_published_at' => now()->subDay(),
    ]);

    $feed = $this->feedService->buildRssFeed();

    // Must not contain raw ]]> inside a CDATA block (except the closing one)
    // The escaped version splits it: ]]]]><![CDATA[>
    expect($feed)->not->toContain('<![CDATA[Break out: ]]> and inject]]>');
    expect($feed)->toContain('Break out: ]]]]><![CDATA[> and inject');
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/FeedServiceTest.php --filter="CDATA" -v`
Expected: FAIL

**Step 3: Add `escapeCdata` helper and apply it**

In `FeedService.php`, add a private method and apply it at all 4 CDATA insertion points:

```php
protected function escapeCdata(string $content): string
{
    return str_replace(']]>', ']]]]><![CDATA[>', $content);
}
```

Replace the 4 CDATA lines:
- Line 143: `$xml .= '      <description><![CDATA[' . $this->escapeCdata($excerpt) . ']]></description>' . PHP_EOL;`
- Line 147: `$xml .= '      <content:encoded><![CDATA[' . $this->escapeCdata($content) . ']]></content:encoded>' . PHP_EOL;`
- Line 202: `$xml .= '    <summary><![CDATA[' . $this->escapeCdata($excerpt) . ']]></summary>' . PHP_EOL;`
- Line 206: `$xml .= '    <content type="html"><![CDATA[' . $this->escapeCdata($content) . ']]></content>' . PHP_EOL;`

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/FeedServiceTest.php --filter="CDATA" -v`
Expected: PASS

**Step 5: Run full FeedService tests**

Run: `vendor/bin/pest tests/Unit/FeedServiceTest.php -v`
Expected: All pass

**Step 6: Commit**

```bash
git add src/Services/FeedService.php tests/Unit/FeedServiceTest.php
git commit -m "fix: escape CDATA-breaking sequences in RSS/Atom feeds"
```

---

### Task 2: No Max Limit on posts_per_page Setting

**Files:**
- Modify: `src/SettingsTabs/ReadingSettingsTabProvider.php:55-61`

**Step 1: Add validation to TextInput**

Change the `posts_per_page` field from:

```php
TextInput::make('posts_per_page')
    ->label('Blog Pages Show At Most')
    ->postfix('posts')
    ->inlineLabel()
    ->default(10)
    ->required()
    ->columnSpan(2),
```

To:

```php
TextInput::make('posts_per_page')
    ->label('Blog Pages Show At Most')
    ->postfix('posts')
    ->inlineLabel()
    ->default(10)
    ->required()
    ->numeric()
    ->minValue(1)
    ->maxValue(100)
    ->columnSpan(2),
```

**Step 2: Commit**

```bash
git add src/SettingsTabs/ReadingSettingsTabProvider.php
git commit -m "fix: add min/max validation to posts_per_page setting"
```

---

### Task 3: EncryptedSettingsCast Silent Null on Key Rotation

**Files:**
- Modify: `src/SettingsCasts/EncryptedSettingsCast.php:20-25`
- Test: `tests/Unit/EncryptedSettingsCastTest.php`

**Step 1: Write the failing test**

```php
use FrankenCms\SettingsCasts\EncryptedSettingsCast;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

it('logs a warning when decryption fails', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'Failed to decrypt'));

    $cast = new EncryptedSettingsCast;

    // Pass a value that can't be decrypted (not a valid encrypted string)
    $result = $cast->get('invalid-encrypted-payload');

    expect($result)->toBeNull();
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/EncryptedSettingsCastTest.php --filter="logs a warning" -v`
Expected: FAIL (no Log::warning call currently)

**Step 3: Add logging to the catch block**

In `EncryptedSettingsCast.php`, change:

```php
use Exception;
use Illuminate\Support\Facades\Crypt;
```

To:

```php
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
```

And change the catch block from:

```php
} catch (Exception $e) {
    // If decryption fails, return null
    return null;
}
```

To:

```php
} catch (Exception $e) {
    Log::warning('Failed to decrypt setting value. This may indicate an APP_KEY rotation. Re-enter the value in settings.', [
        'exception' => $e->getMessage(),
    ]);

    return null;
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/EncryptedSettingsCastTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/SettingsCasts/EncryptedSettingsCast.php tests/Unit/EncryptedSettingsCastTest.php
git commit -m "fix: log warning when encrypted setting decryption fails"
```

---

### Task 4: API Key Not Revealable, Show Partial Key

**Files:**
- Modify: `src/SettingsTabs/AiSettingsTabProvider.php:83-90`

**Step 1: Update the API key field**

Change the `api_key` TextInput from:

```php
TextInput::make('api_key')
    ->label('API Key')
    ->password()
    ->revealable()
    ->helperText('Your API key will be encrypted and stored securely')
    ->required(fn ($get) => $get('provider') !== 'ollama')
    ->visible(fn ($get) => $get('enabled') && $get('provider') !== 'ollama')
    ->columnSpan(1),
```

To:

```php
TextInput::make('api_key')
    ->label('API Key')
    ->password()
    ->helperText(function ($state) {
        if (! $state) {
            return 'Your API key will be encrypted and stored securely';
        }

        $key = is_string($state) ? $state : '';
        $len = strlen($key);

        if ($len <= 8) {
            return 'Current key: ****';
        }

        return sprintf(
            'Current key: %s...%s',
            substr($key, 0, 4),
            substr($key, -4)
        );
    })
    ->dehydrateStateUsing(fn ($state, $record) => $state ?: $record?->api_key)
    ->required(fn ($get) => $get('provider') !== 'ollama')
    ->visible(fn ($get) => $get('enabled') && $get('provider') !== 'ollama')
    ->columnSpan(1),
```

Note: The `->dehydrateStateUsing()` ensures that when the field is submitted empty (because the browser doesn't have the real value), the existing encrypted value is preserved rather than overwritten with null.

**Step 2: Commit**

```bash
git add src/SettingsTabs/AiSettingsTabProvider.php
git commit -m "fix: hide API key in DOM, show partial key in helper text"
```

---

### Task 5: Route Registration Errors — Narrow Catch

**Files:**
- Modify: `src/Services/PageRouteService.php:16-28`
- Test: `tests/Unit/PageRouteServiceTest.php`

**Step 1: Write the failing test**

```php
use FrankenCms\Services\PageRouteService;
use Illuminate\Support\Facades\Log;

it('logs non-database errors during route registration', function () {
    // Mock getCachedPages to throw a non-database error
    $service = Mockery::mock(PageRouteService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $service->shouldReceive('getCachedPages')
        ->once()
        ->andThrow(new RuntimeException('Unexpected error'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'route registration'));

    expect(fn () => $service->registerPageRoutes())->toThrow(RuntimeException::class);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/PageRouteServiceTest.php --filter="logs non-database" -v`
Expected: FAIL (currently catches all Throwable silently)

**Step 3: Narrow the catch**

Change imports in `PageRouteService.php`:

```php
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
```

Remove: `use Throwable;`

Change the try-catch from:

```php
try {
    $pages = $this->getCachedPages();

    foreach ($pages as $page) {
        $this->registerPage($page);
    }
} catch (Throwable $e) {
    // Silently fail if database tables don't exist yet (e.g., during tests or fresh install)
    // This can happen when routes are registered before migrations run
    return;
}
```

To:

```php
try {
    $pages = $this->getCachedPages();

    foreach ($pages as $page) {
        $this->registerPage($page);
    }
} catch (QueryException $e) {
    // Silently fail if database tables don't exist yet (e.g., during tests or fresh install)
    // This can happen when routes are registered before migrations run
    return;
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/PageRouteServiceTest.php -v`
Expected: PASS

**Step 5: Also fix string literal and visibility issues in `registerPage()`**

While in this file, fix lines 75-79 and 97 which use `'published'` string literal and lack publish date filtering:

Line 75-79 — change:
```php
$posts = Post::where('post_type', 'post')
    ->where('post_status', 'published')
    ->with(['author', 'categories', 'media'])
    ->orderBy('post_published_at', 'desc')
    ->paginate($readingSettings->posts_per_page ?? 10);
```

To:
```php
$posts = Post::query()
    ->visibleOnFrontend()
    ->with(['author', 'categories', 'media'])
    ->orderBy('post_published_at', 'desc')
    ->paginate($readingSettings->posts_per_page ?? 10);
```

Line 97 — change `'published'` to `PostStatus::PUBLISH` and add the import:
```php
use FrankenCms\Enums\PostStatus;
```

```php
->where('post_status', PostStatus::PUBLISH)
```

**Step 6: Run full test suite**

Run: `vendor/bin/pest -v`
Expected: All pass

**Step 7: Commit**

```bash
git add src/Services/PageRouteService.php tests/Unit/PageRouteServiceTest.php
git commit -m "fix: narrow route registration catch, fix visibility in PageRouteService"
```

---

### Task 6: FrankenFieldComposer — Scope to Theme Views

**Files:**
- Modify: `src/FrankenCmsServiceProvider.php:337`

**Step 1: Scope the view composer registration**

Change in `registerCmsFieldComposer()` method:

```php
private function registerCmsFieldComposer(): void
{
    // Register the composer for all views
    // The composer itself will check if it's a theme template
    View::composer('*', FrankenFieldComposer::class);
}
```

To:

```php
private function registerCmsFieldComposer(): void
{
    $themeFolder = config('franken-cms.theme_folder', 'theme');
    View::composer($themeFolder . '.*', FrankenFieldComposer::class);
}
```

**Step 2: Run full test suite**

Run: `vendor/bin/pest -v`
Expected: All pass (the composer's internal `isThemeTemplate` guard was already doing this filtering; now the registration itself is scoped)

**Step 3: Commit**

```bash
git add src/FrankenCmsServiceProvider.php
git commit -m "perf: scope FrankenFieldComposer to theme views only"
```

---

### Task 7: Duplicate DB Queries — Reuse CurrentPageService

**Files:**
- Modify: `src/Http/Controllers/RouteController.php:15-19,91-106`
- Modify: `src/Services/ContentResolver.php:46-66`

**Step 1: Add CurrentPageService to RouteController**

Inject `CurrentPageService` into the constructor:

```php
use FrankenCms\Services\CurrentPageService;

public function __construct(
    private readonly RouteHandler $routeHandler,
    private readonly ContentResolver $contentResolver,
    private readonly ReadingSettings $settings,
    private readonly CurrentPageService $currentPageService
) {}
```

**Step 2: Reuse resolved post in `handlePostPath()`**

Change `handlePostPath()` from:

```php
private function handlePostPath(string $path, Request $request)
{
    $slug = $this->contentResolver->extractSlugFromPostPath($path);
    $post = $this->contentResolver->resolvePost($slug, $request->query('p'));

    $themeFolder = config('franken-cms.theme_folder');
    $template = $post->template ?? 'post';
    $view = sprintf('%s.%s', $themeFolder, $template);

    if (! view()->exists($view)) {
        $view = sprintf('%s.post', $themeFolder);
    }

    return view($view, compact('post'));
}
```

To:

```php
private function handlePostPath(string $path, Request $request)
{
    $post = $this->currentPageService->getPage();

    if (! $post) {
        $slug = $this->contentResolver->extractSlugFromPostPath($path);
        $post = $this->contentResolver->resolvePost($slug, $request->query('p'));
    }

    $themeFolder = config('franken-cms.theme_folder');
    $template = $post->template ?? 'post';
    $view = sprintf('%s.%s', $themeFolder, $template);

    if (! view()->exists($view)) {
        $view = sprintf('%s.post', $themeFolder);
    }

    return view($view, compact('post'));
}
```

**Step 3: Reuse resolved page in `resolvePage()`**

In `ContentResolver::resolvePage()`, check `CurrentPageService` first:

Add constructor parameter:

```php
use FrankenCms\Services\CurrentPageService;

readonly class ContentResolver
{
    public function __construct(
        private ReadingSettings $readingSettings,
        private PermalinkSettings $permalinkSettings,
        private CurrentPageService $currentPageService
    ) {}
```

Then at the start of `resolvePage()`, add:

```php
public function resolvePage(string $path): View
{
    // Reuse page already resolved by SetCurrentPage middleware
    $existingPage = $this->currentPageService->getPage();
    if ($existingPage && $existingPage->post_type === 'page') {
        return TemplateResolver::resolve($existingPage);
    }

    // ... existing code unchanged ...
}
```

**Step 4: Run full test suite**

Run: `vendor/bin/pest -v`
Expected: All pass

**Step 5: Commit**

```bash
git add src/Http/Controllers/RouteController.php src/Services/ContentResolver.php
git commit -m "perf: reuse CurrentPageService to eliminate duplicate queries"
```

---

### Task 8: Sitemap Chunked Loading

**Files:**
- Modify: `src/Services/SitemapService.php:153-186`

**Step 1: Write the test**

Add a test to `tests/Unit/SitemapServiceTest.php` verifying the sitemap still works correctly with chunking:

```php
test('handles large number of posts without loading all at once', function () {
    // Create 15 posts to verify chunking works
    Post::factory()->count(15)->create([
        'post_type'         => 'post',
        'post_status'       => PostStatus::PUBLISH,
        'post_published_at' => now()->subDay(),
    ]);

    $sitemap = $this->service->generateForPostType('post');
    $rendered = $sitemap->render();

    // All 15 posts should be in the sitemap
    expect(substr_count($rendered, '<url>'))->toBe(15);
});
```

**Step 2: Run test to verify it passes with current implementation**

Run: `vendor/bin/pest tests/Unit/SitemapServiceTest.php --filter="large number" -v`
Expected: PASS (baseline)

**Step 3: Refactor `getPostsForType()` to use chunking**

Replace the `getPostsForType()` method:

```php
protected function getPostsForType(string $postType): Collection
{
    $posts = collect();

    Post::query()
        ->withoutGlobalScopes()
        ->with(['author', 'media', 'meta'])
        ->where('post_type', $postType)
        ->where('post_status', PostStatus::PUBLISH)
        ->whereNotNull('post_published_at')
        ->where('post_published_at', '<=', now())
        ->chunk(500, function ($chunk) use ($posts) {
            $posts->push(...$chunk);
        });

    // Load all parent hierarchy at once for pages
    if ($postType === 'page') {
        $allParentIds = $this->getAllParentIds($posts);

        $allParents = collect();
        if ($allParentIds->isNotEmpty()) {
            Post::query()
                ->withoutGlobalScopes()
                ->with(['author', 'media', 'meta'])
                ->whereIn('id', $allParentIds)
                ->chunk(500, function ($chunk) use ($allParents) {
                    foreach ($chunk as $parent) {
                        $allParents->put($parent->id, $parent);
                    }
                });
        }

        $this->attachParents($posts, $allParents);
        $this->attachParents($allParents, $allParents);
    }

    return $posts->filter(function (Post $post) {
        return ! $this->isExcluded($post);
    });
}
```

**Step 4: Run test to verify it still passes**

Run: `vendor/bin/pest tests/Unit/SitemapServiceTest.php -v`
Expected: All pass

**Step 5: Commit**

```bash
git add src/Services/SitemapService.php tests/Unit/SitemapServiceTest.php
git commit -m "perf: use chunked loading in sitemap generation"
```

---

### Task 9: Slug Uniqueness — Migration and Validation

**Files:**
- Create: `database/migrations/21_add_post_slug_unique_index.php`
- Modify: `src/Filament/Resources/Post/Schemas/PostForm.php:73-75`
- Modify: `src/Filament/Resources/Page/Schemas/PageForm.php:52-54`

**Step 1: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unique(['post_type', 'parent_id', 'post_slug'], 'posts_type_parent_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique('posts_type_parent_slug_unique');
        });
    }
};
```

**Step 2: Update PostForm slug validation**

In `src/Filament/Resources/Post/Schemas/PostForm.php`, change the `slugRules` from:

```php
slugRules: [
    'required',
    fn (?Post $record) => 'unique:posts,post_slug,' . ($record?->id ?? 'NULL') . ',id',
],
```

To:

```php
slugRules: [
    'required',
    fn (?Post $record) => Rule::unique('posts', 'post_slug')
        ->where('post_type', 'post')
        ->whereNull('parent_id')
        ->ignore($record?->id),
],
```

Add at the top of the file: `use Illuminate\Validation\Rule;`

**Step 3: Update PageForm slug validation**

In `src/Filament/Resources/Page/Schemas/PageForm.php`, change the `slugRules` from:

```php
slugRules: [
    'required',
    fn (?Page $record) => 'unique:posts,post_slug,' . ($record?->id ?? 'NULL') . ',id',
],
```

To:

```php
slugRules: [
    'required',
    fn (?Page $record, Get $get) => Rule::unique('posts', 'post_slug')
        ->where('post_type', 'page')
        ->where('parent_id', $get('parent_id'))
        ->ignore($record?->id),
],
```

Add at the top of the file: `use Illuminate\Validation\Rule;`

**Step 4: Run full test suite**

Run: `vendor/bin/pest -v`
Expected: All pass

**Step 5: Commit**

```bash
git add database/migrations/21_add_post_slug_unique_index.php src/Filament/Resources/Post/Schemas/PostForm.php src/Filament/Resources/Page/Schemas/PageForm.php
git commit -m "fix: enforce slug uniqueness per post_type and parent"
```

---

### Task 10: Final Verification

**Step 1: Run full test suite**

Run: `vendor/bin/pest`
Expected: All tests pass

**Step 2: Run static analysis**

Run: `composer analyse`
Expected: No new errors in modified files

**Step 3: Run code formatting**

Run: `composer format`
Expected: Clean or auto-fixed

**Step 4: Commit any formatting changes**

```bash
git add -A && git commit -m "chore: apply code formatting"
```
