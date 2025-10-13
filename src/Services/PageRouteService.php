<?php

namespace FrankenCms\Services;

use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class PageRouteService
{
    /**
     * Register all pages with route_name as named routes
     */
    public function registerPageRoutes(): void
    {
        $pages = $this->getCachedPages();

        foreach ($pages as $page) {
            $this->registerPage($page);
        }
    }

    /**
     * Register a single page as a named route
     */
    protected function registerPage(array $page): void
    {
        Route::get($page['path'], function () use ($page) {
            $post = Post::findOrFail($page['id']);

            $themeFolder = config('franken-cms.theme_folder');
            $template = $post->template ?? 'page';
            $view = sprintf('%s.%s', $themeFolder, $template);

            // Fallback to default page template if specific template doesn't exist
            if (! view()->exists($view)) {
                $view = sprintf('%s.page', $themeFolder);
            }

            return view($view, ['page' => $post]);
        })->name($page['route_name']);
    }

    /**
     * Get pages with route names, cached for performance
     */
    protected function getCachedPages(): array
    {
        return Cache::remember('franken_cms_page_routes', now()->addDay(), function () {
            return Post::where('post_type', 'page')
                ->whereNotNull('route_name')
                ->where('post_status', 'published')
                ->get()
                ->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'route_name' => $page->route_name,
                        'path' => $page->getHierarchicalPath(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Clear the page routes cache
     */
    public function clearCache(): void
    {
        Cache::forget('franken_cms_page_routes');
    }

    /**
     * Refresh page routes (clear cache and re-register)
     */
    public function refreshRoutes(): void
    {
        $this->clearCache();
        $this->registerPageRoutes();
    }
}