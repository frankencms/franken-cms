# Critical Security Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix three critical security issues blocking beta release: draft content exposure, stored XSS, and eval()-based RCE vector.

**Architecture:** Add a reusable `visibleOnFrontend` query scope to Post model and apply it across all public-facing queries. Switch rich content rendering from unsafe to sanitized HTML. Replace eval() with a custom array parser for template directive options.

**Tech Stack:** PHP 8.4, Laravel, FilamentPHP v5, Pest v4

---

### Task 1: Add `visibleOnFrontend` scope and fix `isPublished()`

**Files:**
- Modify: `src/Models/Post.php:166-169` (fix isPublished), add new scope
- Test: `tests/Unit/PostVisibilityTest.php`

**Step 1: Write failing tests for the scope and isPublished**

Create `tests/Unit/PostVisibilityTest.php`:

```php
<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;

it('scopes visibleOnFrontend to only published posts with past publish date', function () {
    Post::withoutGlobalScopes()->insert([
        ['post_title' => 'Published', 'post_slug' => 'published', 'post_status' => 'published', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Draft', 'post_slug' => 'draft', 'post_status' => 'draft', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Scheduled', 'post_slug' => 'scheduled', 'post_status' => 'published', 'post_published_at' => now()->addWeek(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Private', 'post_slug' => 'private', 'post_status' => 'private', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Pending', 'post_slug' => 'pending', 'post_status' => 'pending', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $visible = Post::query()->visibleOnFrontend()->get();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->post_slug)->toBe('published');
});

it('isPublished returns true only for published posts with past date', function () {
    $published = Post::factory()->make([
        'post_status' => PostStatus::PUBLISH,
        'post_published_at' => now()->subDay(),
    ]);

    $draft = Post::factory()->make([
        'post_status' => PostStatus::DRAFT,
        'post_published_at' => now()->subDay(),
    ]);

    $future = Post::factory()->make([
        'post_status' => PostStatus::PUBLISH,
        'post_published_at' => now()->addWeek(),
    ]);

    expect($published->isPublished())->toBeTrue()
        ->and($draft->isPublished())->toBeFalse()
        ->and($future->isPublished())->toBeFalse();
});
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/PostVisibilityTest.php -v`
Expected: FAIL — `visibleOnFrontend` scope doesn't exist, `isPublished()` uses wrong column names

**Step 3: Implement the scope and fix isPublished**

In `src/Models/Post.php`, add the import at the top:
```php
use Illuminate\Database\Eloquent\Builder;
```

Add the scope method (after `isPublished`):
```php
public function scopeVisibleOnFrontend(Builder $query): Builder
{
    return $query
        ->where('post_status', PostStatus::PUBLISH)
        ->where('post_published_at', '<=', now());
}
```

Fix `isPublished()` (line 166-169) — change from:
```php
public function isPublished(): bool
{
    return $this->status === PostStatus::PUBLISH->value && $this->published_at <= now();
}
```
to:
```php
public function isPublished(): bool
{
    return $this->post_status === PostStatus::PUBLISH && $this->post_published_at?->lte(now());
}
```

Note: `post_status` is cast to `PostStatus` enum, so compare to the enum directly (not `->value`). Use null-safe `?->lte()` to handle null `post_published_at`.

**Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/PostVisibilityTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Models/Post.php tests/Unit/PostVisibilityTest.php
git commit -m "feat: add visibleOnFrontend scope and fix isPublished column names"
```

---

### Task 2: Apply scope to PostController and ContentResolver

**Files:**
- Modify: `src/Http/Controllers/PostController.php:20-22`
- Modify: `src/Services/ContentResolver.php:26,110,132-141,143-164`
- Test: `tests/Unit/ContentResolverTest.php`

**Step 1: Write failing tests**

Create `tests/Unit/ContentResolverTest.php`:

```php
<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Services\ContentResolver;

beforeEach(function () {
    // Set required settings
    $readingSettings = app(\FrankenCms\Settings\ReadingSettings::class);
    $readingSettings->post_page = 'blog';
    $readingSettings->save();
});

it('resolvePost does not return draft posts', function () {
    Post::withoutGlobalScopes()->insert([
        'post_title' => 'Draft Post', 'post_slug' => 'draft-post', 'post_status' => 'draft',
        'post_published_at' => now()->subDay(), 'post_type' => 'post',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $resolver = app(ContentResolver::class);

    $resolver->resolvePost('draft-post');
})->throws(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

it('resolvePost does not return future-scheduled posts', function () {
    Post::withoutGlobalScopes()->insert([
        'post_title' => 'Future Post', 'post_slug' => 'future-post', 'post_status' => 'published',
        'post_published_at' => now()->addWeek(), 'post_type' => 'post',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $resolver = app(ContentResolver::class);

    $resolver->resolvePost('future-post');
})->throws(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

it('resolvePost returns published posts with past date', function () {
    Post::withoutGlobalScopes()->insert([
        'post_title' => 'Live Post', 'post_slug' => 'live-post', 'post_status' => 'published',
        'post_published_at' => now()->subDay(), 'post_type' => 'post',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $resolver = app(ContentResolver::class);
    $post = $resolver->resolvePost('live-post');

    expect($post->post_slug)->toBe('live-post');
});

it('isPostPath matches exact segment boundaries', function () {
    $resolver = app(ContentResolver::class);

    expect($resolver->isPostPath('blog'))->toBeTrue()
        ->and($resolver->isPostPath('blog/my-post'))->toBeTrue()
        ->and($resolver->isPostPath('blogging-tips'))->toBeFalse()
        ->and($resolver->isPostPath('blogger'))->toBeFalse();
});

it('extractSlugFromPostPath extracts slug correctly', function () {
    $resolver = app(ContentResolver::class);

    expect($resolver->extractSlugFromPostPath('blog/my-post'))->toBe('my-post')
        ->and($resolver->extractSlugFromPostPath('blog/2024/01/my-post'))->toBe('2024/01/my-post');
});
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/ContentResolverTest.php -v`
Expected: FAIL — draft and future posts are still returned, `isPostPath` matches too broadly

**Step 3: Apply scope to ContentResolver**

In `src/Services/ContentResolver.php`:

Fix `findPostBySlug` (line 137-141):
```php
private function findPostBySlug(string $slug): ?Post
{
    return Post::with('parent')->visibleOnFrontend()->where('post_slug', $slug)->first();
}
```

Fix `findPostById` (line 132-134):
```php
private function findPostById(?string $id): ?Post
{
    return $id ? Post::visibleOnFrontend()->find($id) : null;
}
```

Fix `findByCustomPermalink` (line 143-165) — change `Post::query()` to include scope:
```php
$query = Post::query()->visibleOnFrontend();
```

Fix `resolveHomePage` (line 26) — add published check to homepage query:
```php
$page = Page::with('parent')
    ->where('post_slug', $homePage)
    ->where('post_status', PostStatus::PUBLISH)
    ->firstOrFail();
```
Add import: `use FrankenCms\Enums\PostStatus;`

Fix `resolveHierarchicalPage` (line 110) — add published check inside the loop:
```php
$query = Page::withoutGlobalScopes()
    ->where('post_slug', $slug)
    ->where('post_status', PostStatus::PUBLISH);
```

Fix `isPostPath` (line 90-94):
```php
public function isPostPath(string $path): bool
{
    $postPage = $this->readingSettings->post_page;

    return $postPage && ($path === $postPage || str_starts_with($path, $postPage . '/'));
}
```

Fix `extractSlugFromPostPath` (line 96-99):
```php
public function extractSlugFromPostPath(string $path): string
{
    return trim(substr($path, strlen($this->readingSettings->post_page) + 1), '/');
}
```

**Step 4: Apply scope to PostController**

In `src/Http/Controllers/PostController.php`, change `index` method (line 20-22):
```php
$posts = Post::query()
    ->visibleOnFrontend()
    ->orderBy('post_published_at', 'desc')
    ->paginate($this->settings->posts_per_page);
```

**Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/ContentResolverTest.php tests/Unit/PostVisibilityTest.php -v`
Expected: PASS

**Step 6: Run full test suite to check for regressions**

Run: `vendor/bin/pest`
Expected: All tests pass

**Step 7: Commit**

```bash
git add src/Http/Controllers/PostController.php src/Services/ContentResolver.php tests/Unit/ContentResolverTest.php
git commit -m "fix: filter draft/scheduled/private content from public frontend queries

Apply visibleOnFrontend scope to PostController and ContentResolver.
Fix isPostPath prefix matching to use segment boundaries.
Fix extractSlugFromPostPath to use prefix-only removal."
```

---

### Task 3: Switch toUnsafeHtml() to toHtml()

**Files:**
- Modify: `src/Models/Post.php:422-463`
- Test: `tests/Unit/PostRichContentTest.php`

**Step 1: Write failing test**

Create `tests/Unit/PostRichContentTest.php`:

```php
<?php

use FrankenCms\Models\Post;

it('renderRichContent sanitizes script tags from output', function () {
    $post = Post::factory()->create([
        'post_content' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => [
                        ['type' => 'text', 'text' => 'Hello world'],
                    ],
                ],
            ],
        ],
    ]);

    $html = $post->renderRichContent('post_content');

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('Hello world');
});

it('renderRichContent strips event handler attributes', function () {
    $post = Post::factory()->create([
        'post_content' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => [
                        ['type' => 'text', 'text' => 'Safe content'],
                    ],
                ],
            ],
        ],
    ]);

    $html = $post->renderRichContent('post_content');

    expect($html)->not->toContain('onerror')
        ->and($html)->not->toContain('onclick');
});
```

**Step 2: Run test to verify current behavior**

Run: `vendor/bin/pest tests/Unit/PostRichContentTest.php -v`
Expected: The tests may pass since the test content doesn't contain scripts — but the point is ensuring the sanitizer is active. This verifies the code path works.

**Step 3: Switch to toHtml()**

In `src/Models/Post.php`, update the `renderRichContent` method.

Change the docblock (line 422-428) from:
```php
/**
 * Override renderRichContent to add enhanced image attributes
 *
 * Uses toUnsafeHtml() instead of toHtml() to avoid Filament's HTML sanitizer
 * which encodes special characters (like ' to &#039; and @ to &#64;).
 * The content is already sanitized by TipTap during editing.
 */
```
to:
```php
/**
 * Override renderRichContent to add enhanced image attributes
 *
 * Uses Filament's built-in HTML sanitizer (Symfony HtmlSanitizer)
 * to prevent stored XSS from rich editor content.
 */
```

Change line 453-454 from:
```php
// Use toUnsafeHtml() to avoid HTML entity encoding of special characters
$html = $renderer->toUnsafeHtml();
```
to:
```php
$html = $renderer->toHtml();
```

**Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/PostRichContentTest.php -v`
Expected: PASS

**Step 5: Run full test suite**

Run: `vendor/bin/pest`
Expected: All tests pass

**Step 6: Commit**

```bash
git add src/Models/Post.php tests/Unit/PostRichContentTest.php
git commit -m "fix: switch renderRichContent from toUnsafeHtml to toHtml

Use Filament's built-in Symfony HTML sanitizer to prevent stored XSS.
Client-side TipTap sanitization alone is insufficient as requests
can be crafted to bypass the editor."
```

---

### Task 4: Replace eval() with safe array parser

**Files:**
- Modify: `src/Services/TemplateFieldExtractor.php:273-293`
- Test: `tests/Unit/TemplateFieldExtractorTest.php` (existing file — add new tests)

**Step 1: Write failing tests for the new parser**

Append to existing `tests/Unit/TemplateFieldExtractorTest.php`:

```php
it('parses options with numeric values', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenNumber('quantity', ['min' => 0, 'max' => 100, 'step' => 0.5])";
    $fields = $parser->parseContent($content);

    expect($fields['quantity']['options']['min'])->toBe(0)
        ->and($fields['quantity']['options']['max'])->toBe(100)
        ->and($fields['quantity']['options']['step'])->toBe(0.5);
});

it('parses options with boolean and null values', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('field', ['required' => true, 'disabled' => false, 'default' => null])";
    $fields = $parser->parseContent($content);

    expect($fields['field']['options']['required'])->toBeTrue()
        ->and($fields['field']['options']['disabled'])->toBeFalse()
        ->and($fields['field']['options']['default'])->toBeNull();
});

it('parses options with nested arrays', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenSelect('color', ['options' => ['red' => 'Red', 'blue' => 'Blue']])";
    $fields = $parser->parseContent($content);

    expect($fields['color']['options']['options'])->toBe(['red' => 'Red', 'blue' => 'Blue']);
});

it('parses options with double-quoted strings', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = '@frankenText(\'title\', ["label" => "Page Title", "placeholder" => "Enter title"])';
    $fields = $parser->parseContent($content);

    expect($fields['title']['options']['label'])->toBe('Page Title')
        ->and($fields['title']['options']['placeholder'])->toBe('Enter title');
});

it('parses options with escaped quotes in strings', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('field', ['label' => 'It\\'s a label'])";
    $fields = $parser->parseContent($content);

    expect($fields['field']['options']['label'])->toBe("It's a label");
});

it('handles empty options array', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('field', [])";
    $fields = $parser->parseContent($content);

    expect($fields['field']['options'])->toBe([]);
});

it('throws on unparseable options', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('field', [fn () => 'value'])";
    $parser->parseContent($content);
})->throws(RuntimeException::class);

it('parses options with integer keys (sequential array)', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenSelect('size', ['options' => ['small', 'medium', 'large']])";
    $fields = $parser->parseContent($content);

    expect($fields['size']['options']['options'])->toBe(['small', 'medium', 'large']);
});
```

**Step 2: Run tests to verify they pass with current eval-based parser**

Run: `vendor/bin/pest tests/Unit/TemplateFieldExtractorTest.php -v`
Expected: Most PASS (eval handles these), except possibly the error-handling test for closures

**Step 3: Replace parseOptions with safe parser**

In `src/Services/TemplateFieldExtractor.php`, replace the `parseOptions` method (line 273-293) with:

```php
/**
 * Parse the options array from the directive without using eval()
 *
 * Handles: quoted strings, numbers, booleans, null, and nested arrays.
 * Throws on closures, function calls, or other PHP expressions.
 */
protected function parseOptions(string $optionsString): array
{
    $optionsString = trim($optionsString, '[]');

    if (empty(trim($optionsString))) {
        return [];
    }

    $pos = 0;

    return $this->parseArray($optionsString, $pos);
}

/**
 * Recursively parse a PHP-style array from a string
 */
private function parseArray(string $input, int &$pos): array
{
    $result = [];
    $length = strlen($input);
    $index = 0;

    while ($pos < $length) {
        $this->skipWhitespace($input, $pos);

        if ($pos >= $length) {
            break;
        }

        // Check for nested array opening
        if ($input[$pos] === ']') {
            $pos++; // consume closing bracket
            break;
        }

        // Skip commas between elements
        if ($input[$pos] === ',') {
            $pos++;
            continue;
        }

        // Parse value or key => value
        $value = $this->parseValue($input, $pos);

        $this->skipWhitespace($input, $pos);

        // Check if this is a key => value pair
        if ($pos + 1 < $length && $input[$pos] === '=' && $input[$pos + 1] === '>') {
            $pos += 2; // skip =>
            $this->skipWhitespace($input, $pos);
            $key = $value;
            $value = $this->parseValue($input, $pos);
            $result[$key] = $value;
        } else {
            $result[$index++] = $value;
        }
    }

    return $result;
}

/**
 * Parse a single value: string, number, boolean, null, or nested array
 */
private function parseValue(string $input, int &$pos): mixed
{
    $this->skipWhitespace($input, $pos);

    if ($pos >= strlen($input)) {
        throw new RuntimeException('Unexpected end of options string');
    }

    $char = $input[$pos];

    // Quoted string
    if ($char === "'" || $char === '"') {
        return $this->parseString($input, $pos);
    }

    // Nested array
    if ($char === '[') {
        $pos++; // skip opening bracket
        return $this->parseArray($input, $pos);
    }

    // Keyword or number — read until delimiter
    $start = $pos;
    while ($pos < strlen($input) && ! in_array($input[$pos], [',', ']', '=', ' ', "\t", "\n", "\r"], true)) {
        $pos++;
    }

    $token = trim(substr($input, $start, $pos - $start));

    return match (true) {
        $token === 'true'                              => true,
        $token === 'false'                             => false,
        $token === 'null'                              => null,
        is_numeric($token) && ! str_contains($token, '.') => (int) $token,
        is_numeric($token)                             => (float) $token,
        default                                        => throw new RuntimeException(
            "Unsupported expression in field options: '{$token}'. Only strings, numbers, booleans, null, and arrays are allowed."
        ),
    };
}

/**
 * Parse a quoted string, handling escape sequences
 */
private function parseString(string $input, int &$pos): string
{
    $quote = $input[$pos];
    $pos++; // skip opening quote
    $result = '';
    $length = strlen($input);

    while ($pos < $length) {
        $char = $input[$pos];

        if ($char === '\\' && $pos + 1 < $length) {
            $next = $input[$pos + 1];
            $result .= match ($next) {
                '\\' => '\\',
                $quote => $quote,
                'n' => "\n",
                't' => "\t",
                default => '\\' . $next,
            };
            $pos += 2;
            continue;
        }

        if ($char === $quote) {
            $pos++; // skip closing quote
            return $result;
        }

        $result .= $char;
        $pos++;
    }

    throw new RuntimeException('Unterminated string in field options');
}

/**
 * Skip whitespace characters
 */
private function skipWhitespace(string $input, int &$pos): void
{
    while ($pos < strlen($input) && in_array($input[$pos], [' ', "\t", "\n", "\r"], true)) {
        $pos++;
    }
}
```

**Step 4: Run the extractor tests**

Run: `vendor/bin/pest tests/Unit/TemplateFieldExtractorTest.php -v`
Expected: ALL PASS

**Step 5: Run full test suite**

Run: `vendor/bin/pest`
Expected: All tests pass

**Step 6: Commit**

```bash
git add src/Services/TemplateFieldExtractor.php tests/Unit/TemplateFieldExtractorTest.php
git commit -m "fix: replace eval() with safe recursive array parser in TemplateFieldExtractor

Remove RCE vector from directive options parsing. The new parser
handles strings, numbers, booleans, null, and nested arrays without
executing arbitrary PHP code. Unsupported expressions (closures,
function calls) throw a clear RuntimeException."
```

---

### Task 5: Final verification

**Step 1: Add scheduled/private factory states**

In `database/factories/PostFactory.php`, add after the `published()` method:

```php
/**
 * Indicate that the post is scheduled for future publication.
 */
public function scheduled(): static
{
    return $this->state(fn (array $attributes) => [
        'post_status' => PostStatus::PUBLISH,
        'post_published_at' => now()->addWeek(),
    ]);
}

/**
 * Indicate that the post is private.
 */
public function private(): static
{
    return $this->state(fn (array $attributes) => [
        'post_status' => PostStatus::PRIVATE,
    ]);
}
```

**Step 2: Run full test suite with coverage**

Run: `herd coverage ./vendor/bin/pest --coverage`
Expected: All tests pass

**Step 3: Run static analysis**

Run: `composer analyse`
Expected: No new errors introduced

**Step 4: Run formatter**

Run: `composer format`

**Step 5: Commit any formatting changes**

```bash
git add -A
git commit -m "chore: add scheduled/private factory states, format code"
```
