<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Settings\SitemapSettings;
use FrankenCms\Sitemap\Sitemap;
use FrankenCms\Sitemap\Url;
use FrankenCms\Sitemap\UrlExclusionMatcher;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Routing\Middleware\ValidateSignature;
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

    /**
     * Framework middleware FQCNs that mark a route as gated (not publicly indexable),
     * for routes that reference middleware classes directly instead of via alias
     */
    protected const GATED_MIDDLEWARE_CLASSES = [
        Authenticate::class,
        AuthenticateWithBasicAuth::class,
        Authorize::class,
        RequirePassword::class,
        EnsureEmailIsVerified::class,
        ValidateSignature::class,
    ];

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

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
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
     * @param  array<int, string>  $pageRouteNames
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
     * @param  array<int, string>  $pageRouteNames
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
     * @param  array<int, mixed>  $middleware
     */
    protected function isGated(array $middleware): bool
    {
        foreach ($middleware as $item) {
            if (! is_string($item)) {
                continue;
            }

            if ($item === 'auth' || str_starts_with($item, 'auth:') || str_starts_with($item, 'auth.')) {
                return true;
            }

            if (str_starts_with($item, 'can:')) {
                return true;
            }

            if (in_array($item, self::GATED_MIDDLEWARE, true)) {
                return true;
            }

            if (in_array(ltrim($item, '\\'), self::GATED_MIDDLEWARE_CLASSES, true)) {
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
