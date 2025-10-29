<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Models\Menu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * Get menu by slug with caching
     */
    public function getMenu(string $slug): ?Menu
    {
        return Cache::remember(
            "menu.{$slug}",
            config('franken-cms.menu_cache', 3600),
            fn () => Menu::findBySlug($slug)
        );
    }

    /**
     * Get menu items with hierarchical structure
     */
    public function getMenuItems(string $slug): array
    {
        $menu = $this->getMenu($slug);

        if (! $menu) {
            return [];
        }

        return $menu->getCachedMenuItems();
    }

    /**
     * Get flattened menu items (useful for breadcrumbs, etc.)
     */
    public function getFlatMenuItems(string $slug): Collection
    {
        $menuItems = $this->getMenuItems($slug);
        return $this->flattenMenuItems($menuItems);
    }

    /**
     * Find menu item by URL or route
     */
    public function findActiveMenuItem(string $slug, string $currentUrl): ?array
    {
        $menuItems = $this->getFlatMenuItems($slug);

        return $menuItems->first(function ($item) use ($currentUrl) {
            return $item['url'] === $currentUrl ||
                   $this->isUrlMatch($item['url'], $currentUrl);
        });
    }

    /**
     * Get breadcrumb from menu structure
     */
    public function getBreadcrumb(string $slug, string $currentUrl): array
    {
        $menuItems = $this->getMenuItems($slug);
        return $this->findBreadcrumbPath($menuItems, $currentUrl);
    }

    /**
     * Clear all menu caches
     */
    public function clearAllMenuCaches(): void
    {
        $menuSlugs = Menu::pluck('slug');

        foreach ($menuSlugs as $slug) {
            Cache::forget("menu.{$slug}");
            Cache::forget("menu.{$slug}.items");
        }
    }
    //
    //    /**
    //     * Render menu as HTML string
    //     */
    //    public function renderMenu(string $slug, string $template = 'default'): string
    //    {
    //        $menuItems = $this->getMenuItems($slug);
    //
    //        if (empty($menuItems)) {
    //            return '';
    //        }
    //
    //        return view("franken-cms::menu.{$template}", [
    //            'menuItems' => $menuItems,
    //            'slug'      => $slug,
    //        ])->render();
    //    }

    /**
     * Recursively flatten menu items
     */
    protected function flattenMenuItems(array $items): Collection
    {
        $flattened = collect();

        foreach ($items as $item) {
            $flattened->push($item);

            if (! empty($item['children'])) {
                $flattened = $flattened->merge(
                    $this->flattenMenuItems($item['children'])
                );
            }
        }

        return $flattened;
    }

    /**
     * Check if URLs match (handles wildcards and partial matches)
     */
    public function isUrlMatch(string $menuUrl, string $currentUrl): bool
    {
        // Remove query strings and fragments
        $menuUrl = parse_url($menuUrl, PHP_URL_PATH) ?? $menuUrl;
        $currentUrl = parse_url($currentUrl, PHP_URL_PATH) ?? $currentUrl;

        // Exact match
        if ($menuUrl === $currentUrl) {
            return true;
        }

        // Check if current URL starts with menu URL (for parent menu highlighting)
        return str_starts_with($currentUrl, rtrim($menuUrl, '/') . '/');
    }

    /**
     * Add active state properties to menu items
     */
    public function addActiveState(array &$menuItems, string $currentUrl): void
    {
        foreach ($menuItems as &$item) {
            // Exact match for current page
            $menuUrl = parse_url($item['url'], PHP_URL_PATH) ?? $item['url'];
            $current = parse_url($currentUrl, PHP_URL_PATH) ?? $currentUrl;

            // Normalize URLs by removing trailing slashes for comparison
            $item['active'] = rtrim($menuUrl, '/') === rtrim($current, '/');

            // Parent/ancestor match (current URL starts with menu URL, but not exact match)
            $item['active_ancestor'] = $this->isUrlMatch($item['url'], $currentUrl) && ! $item['active'];

            // Recursively process children
            if (! empty($item['children'])) {
                $this->addActiveState($item['children'], $currentUrl);
            }
        }
    }

    /**
     * Recursively find breadcrumb path
     */
    protected function findBreadcrumbPath(array $items, string $currentUrl, array $path = []): array
    {
        foreach ($items as $item) {
            $currentPath = array_merge($path, [$item]);

            if ($this->isUrlMatch($item['url'], $currentUrl)) {
                return $currentPath;
            }

            if (! empty($item['children'])) {
                $childPath = $this->findBreadcrumbPath($item['children'], $currentUrl, $currentPath);
                if (! empty($childPath)) {
                    return $childPath;
                }
            }
        }

        return [];
    }
}
