# Medium-Severity Fixes Design

**Date:** 2026-02-19
**Status:** Approved

## Overview

Nine medium-severity issues identified during beta readiness review. All confirmed in codebase.

---

## Section 1: Data Integrity & Security

### 1. CDATA Injection in RSS Feeds

**File:** `src/Services/FeedService.php`

Content placed inside CDATA blocks is not escaped. A `]]>` sequence in content breaks the CDATA section and can inject arbitrary XML.

**Fix:** Apply standard CDATA escaping (`str_replace(']]>', ']]]]><![CDATA[>', $content)`) before placing content into CDATA blocks. Four locations in `buildRssFeed()` and `buildAtomFeed()`.

### 4. Slug Uniqueness Not Enforced at DB Level

**Files:** New migration, Post/Page Filament resource schemas

No unique constraint on `post_slug`. Duplicate slugs cause routing ambiguity.

**Fix:**
- Add migration with composite unique index on `(post_type, parent_id, post_slug)`
- Note: NULL parent_id values are treated as distinct by most databases, so root-level duplicates can slip through the DB constraint
- Add Filament validation rules (`Rules\Unique` scoped to post_type and parent_id) as primary enforcement
- DB index serves as safety net

### 5. API Key Revealable in DOM

**Files:** `src/SettingsTabs/AiSettingsTabProvider.php`

The `.revealable()` modifier exposes the decrypted API key in the browser DOM.

**Fix:**
- Remove `.revealable()` from the API key field
- Use `->dehydrateStateUsing()` to prevent sending the full key to the browser
- Show partial key in helper text when one is stored: `"Current key: sk-...a3b7"` (prefix + last 4 characters)
- Admin can always overwrite with a new key

### 9. EncryptedSettingsCast Silently Returns Null on Key Rotation

**File:** `src/SettingsCasts/EncryptedSettingsCast.php`

When APP_KEY is rotated, old encrypted values fail to decrypt and `get()` silently returns `null`. No warning is logged.

**Fix:**
- Add `Log::warning()` when decryption fails, including the setting context (but not the encrypted value)
- Keep `null` as the return value (correct fallback behavior)
- Gives admins visibility into key rotation issues

---

## Section 2: Performance & Efficiency

### 2. No Max Limit on posts_per_page Setting

**File:** `src/SettingsTabs/ReadingSettingsTabProvider.php`

The `posts_per_page` setting accepts any integer. A very large value causes memory exhaustion via `paginate()`.

**Fix:** Add `->numeric()->minValue(1)->maxValue(100)` to the Filament form field.

### 3. Sitemap Loads All Posts Into Memory

**File:** `src/Services/SitemapService.php`

`getPostsForType()` calls `->get()` which loads all posts at once. Large sites will OOM.

**Fix:** Switch from `->get()` to `->chunk(500)` and build the sitemap incrementally using `Sitemap::add()` in a loop. Apply chunking to the parent-loading logic for pages as well.

### 6. FrankenFieldComposer Registered on All Views

**File:** `src/FrankenCmsServiceProvider.php`

`View::composer('*', FrankenFieldComposer::class)` runs the composer on every view including admin panels and error pages. The composer has an internal guard but the registration is unnecessarily broad.

**Fix:** Change to `View::composer($themeFolder . '.*', FrankenFieldComposer::class)` using the configured theme folder name.

### 7. Duplicate DB Queries Between Middleware and Controller

**Files:** `src/Http/Middleware/SetCurrentPage.php`, `src/Http/Controllers/RouteController.php`

The middleware resolves a post/page and stores it in `CurrentPageService`, then the controller re-queries for the same entity.

**Fix:** Have the controller check `CurrentPageService` first. If the resolved entity is already available, use it instead of re-querying.

---

## Section 3: Error Handling

### 8. Route Registration Errors Silently Swallowed

**File:** `src/Services/PageRouteService.php`

`catch (Throwable $e)` silently swallows all errors including real bugs. Only database-missing-table errors should be suppressed.

**Fix:**
- Narrow the catch to `QueryException` and `PDOException` for table-not-found scenarios
- Log and re-throw all other exceptions
- Preserves graceful handling during fresh installs while surfacing real bugs
