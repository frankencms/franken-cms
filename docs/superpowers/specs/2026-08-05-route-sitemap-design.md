# App Routes Sitemap — Design

**Date:** 2026-08-05
**Status:** Approved

## Motivation

FrankenCMS generates sitemaps for its own content (`sitemap-pages.xml`, `sitemap-posts.xml`) and lets users hand-add external sitemap URLs to the index via the `custom_sitemaps` setting. But host applications typically define their own non-CMS routes (`/pricing`, `/contact`, `Route::view()` pages, etc.), and today those pages are invisible to search engines unless the developer builds and registers a sitemap by hand.

This feature auto-generates a third sitemap, `sitemap-routes.xml`, covering the host app's own static public routes.

## Decisions

1. **Discovery model:** Auto-discovery with conservative heuristics, plus per-route macros to override (`->sitemap()` / `->skipSitemap()`).
2. **Default state:** Enabled by default for new installs and upgrades (`include_app_routes = true`).
3. **Architecture:** A new dedicated `RouteSitemapService`; macros store metadata on the route's action array. `SitemapService` stays focused on CMS content.

## Discovery rules

`FrankenCms\Services\RouteSitemapService` walks `Route::getRoutes()` and includes a route only if **all** of the following hold:

- Responds to GET (`GET` present in the route's methods; HEAD companion ignored)
- URI contains no parameters (no `{` in the URI)
- Route's gathered middleware includes `web`
- No domain restriction (`$route->getDomain()` is null) — `url($uri)` would build wrong URLs otherwise
- Not a package route — skip by exact/prefixed route name (`robots`, `feed.*`, `sitemap`, `sitemap.*`) or a controller in the `FrankenCms\` namespace
- Not a CMS page route — `PageRouteService::registerPageRoutes()` registers published pages as named closure routes (user-chosen names, so name prefixes can't catch them); skip any route whose name appears in the page-route names from `PageRouteService` — those pages are already in `sitemap-pages.xml`
- Not a vendor route — skip route-name prefixes: `filament.`, `livewire.`, `debugbar.`, `horizon.`, `telescope.`, `pulse.`, `ignition.`
- Not gated — skip if gathered middleware contains `auth`, any `auth:*` variant, `verified`, `signed`, `password.confirm`, or `guest` (`guest` auto-excludes Breeze/Fortify login and register pages)
- Not a `Route::redirect()` route (action controller is `Illuminate\Routing\RedirectController`) — the redirect *target* belongs in a sitemap, not the redirecting URL. `Route::view()` routes (ViewController) are included.
- URL does not match any `excluded_paths` wildcard pattern (identical matching to posts/pages)

Unnamed routes that pass all filters are included — name is only consulted for the skip lists.

### Macro overrides

Registered on `Illuminate\Routing\Route` (Macroable) in `FrankenCmsServiceProvider::boot()`:

- `->sitemap(?float $priority = null, ?string $changeFrequency = null): Route` — force-include the route (overrides the gated/vendor/package-name skips, but never the structural requirements: GET, parameterless, no domain, `web` middleware) and optionally override metadata for this route.
- `->skipSitemap(): Route` — force-exclude.

Precedence: `excluded_paths` is evaluated last and always wins, including over `->sitemap()` — the site operator can remove any URL from the sitemap at runtime without a code deploy.

Metadata is written into the route's **action array** (e.g. `$this->action['sitemap'] = ['include' => true, 'priority' => 0.8, 'change_frequency' => 'monthly']`).

Rationale: action arrays survive `route:cache`, live on the route object itself (no registry, no static state, Octane-safe), and — unlike `defaults()` — cannot leak into parameter binding.

## Serving

- New route: `Route::get('sitemap-routes.xml', [SitemapController::class, 'routes'])->name('sitemap.routes')` in `routes/web.php`.
- `SitemapController::routes()` returns 404 when the sitemap system is disabled **or** `include_app_routes` is off; otherwise renders `RouteSitemapService` output as `text/xml`.
- XML is built with the in-house `FrankenCms\Sitemap\{Sitemap, Url}` classes.
- Each URL gets `priority` / `changefreq` from `default_priority` / `default_change_frequency` settings unless a macro overrides them. **No `lastmod`** — the last-change time of a coded page is unknowable; omitting the tag is spec-valid.
- `SitemapService::generate()` adds a `sitemap-routes.xml` entry to the index when `include_app_routes` is true.

## Settings & UI

- New property `public bool $include_app_routes;` on `SitemapSettings`, with a settings migration defaulting to `true`.
- New Filament `Toggle` in `SitemapSettingsTabProvider` beside `include_images` (label: "App routes sitemap", helper text explaining auto-discovery).
- No other new settings; `excluded_paths`, `default_priority`, `default_change_frequency` apply to route URLs.
- The wildcard matcher currently private in `SitemapService::isExcluded()` is extracted to a shared class (`FrankenCms\Sitemap\UrlExclusionMatcher`) used by both services.

## Caching

- The routes sitemap is **not cached**: generation is an in-memory walk of the route table (zero DB queries), and routes change only at deploy time — a forever-cache would go stale after deploys with no invalidation hook.
- The sitemap index stays forever-cached as today. Saving `SitemapSettings` clears it via the existing `ClearSitemapCacheListener`, so toggling `include_app_routes` immediately adds/removes the index entry.

## Edge cases

- Zero eligible routes → serve a valid empty `<urlset>` (200), not a 404: the cached index may already reference the file, and an empty sitemap is spec-legal.
- Macro arguments are trusted developer input: priority rendered through the shared one-decimal formatter; allowed `changefreq` values documented, not validated at runtime.

## Testing (TDD)

- **Unit — discovery matrix:** register real routes inside tests and assert each rule: parameterized skipped, `auth`/`guest`/`signed`/`verified` skipped, vendor name-prefixes skipped, package names skipped, redirect routes skipped, domain routes skipped, `Route::view()` included, unnamed included, `->sitemap()` force-include + metadata override, `->skipSitemap()` force-exclude, `excluded_paths` filtering, settings defaults applied.
- **Feature:** `sitemap-routes.xml` returns XML when enabled, 404 when either toggle is off; index contains/omits the entry per toggle state.
- **Docs:** README section covering the feature, the toggle, and both macros.

## Out of scope

- Parameterized routes (no way to enumerate their URLs); developers can point `custom_sitemaps` at a hand-built sitemap for those.
- `lastmod` for route URLs.
- Per-route settings UI in Filament — overrides are code-level macros only.
