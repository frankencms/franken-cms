# Critical Security Fixes Design

Three critical issues identified by multi-agent beta readiness review. All three were flagged by claude-opus, codex-5.3-high, and gemini-3-pro independently.

## Fix 1: Draft Content Publicly Exposed

**Problem:** `PostController::index()` and `ContentResolver` methods (`findPostBySlug`, `findPostById`, `findByCustomPermalink`) query posts without filtering by `post_status` or `post_published_at`. Draft, private, scheduled, and pending posts are visible on the public frontend.

**Solution:** Add `scopeVisibleOnFrontend()` query scope to the Post model and apply it to all public-facing queries.

```php
public function scopeVisibleOnFrontend(Builder $query): Builder
{
    return $query
        ->where('post_status', PostStatus::PUBLISH)
        ->where('post_published_at', '<=', now());
}
```

**Files to modify:**
- `src/Models/Post.php` — add scope, fix `isPublished()` column names (`$this->status` -> `$this->post_status`, `$this->published_at` -> `$this->post_published_at`)
- `src/Http/Controllers/PostController.php` — add `->visibleOnFrontend()`
- `src/Services/ContentResolver.php` — add scope to `findPostBySlug()`, `findPostById()`, `findByCustomPermalink()`, homepage query, and hierarchical page loop. Fix `isPostPath()` prefix matching (segment-boundary) and `extractSlugFromPostPath()` (prefix-only removal)

## Fix 2: Stored XSS via toUnsafeHtml()

**Problem:** `Post::renderRichContent()` uses `$renderer->toUnsafeHtml()` which bypasses Filament's HTML sanitizer. Any authenticated user can craft a POST request with `<script>` tags that render on the public frontend.

**Solution:** Switch to `$renderer->toHtml()`. Filament's built-in `Str::sanitizeHtml()` (Symfony HTML Sanitizer) handles output sanitization. Blade directives are only used in template fields, not in rich editor content, so this is safe.

**Files to modify:**
- `src/Models/Post.php` — change `toUnsafeHtml()` to `toHtml()`, update docblock/comments

## Fix 3: Replace eval() in TemplateFieldExtractor

**Problem:** `parseOptions()` uses `eval("return [{$optionsString}]")` to parse directive options from Blade templates. While templates are developer-authored, this is an RCE vector if templates ever come from untrusted sources and a stability risk (syntax errors crash the app).

**Solution:** Replace with a custom recursive parser that handles the limited syntax used in directive options: quoted strings, numbers, booleans, null, and nested arrays. Closures/expressions are rejected gracefully.

**Files to modify:**
- `src/Services/TemplateFieldExtractor.php` — replace `parseOptions()` method

## Tests

- Scope: draft/scheduled/private posts excluded from frontend queries
- XSS: `renderRichContent()` sanitizes script tags
- Parser: options parser handles strings, numbers, booleans, nested arrays, and rejects invalid input
