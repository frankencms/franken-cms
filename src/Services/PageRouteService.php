<?php

namespace FrankenCms\Services;

use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Throwable;

class PageRouteService
{
    /**
     * Register all pages with route_name as named routes
     */
    public function registerPageRoutes(): void
    {
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

    /**
     * Register a single page as a named route
     */
    protected function registerPage(array $page): void
    {
        Route::get($page['path'], function () use ($page) {
            $post = Post::withoutGlobalScopes()->findOrFail($page['id']);

            // Check if this page is the homepage - redirect to root
            $readingSettings = app(\FrankenCms\Settings\ReadingSettings::class);
            if ($readingSettings->home_page && $post->post_slug === $readingSettings->home_page) {
                return redirect('/');
            }

            $themeFolder = config('franken-cms.theme_folder');
            $template = $post->template ?? 'page';
            $view = sprintf('%s.%s', $themeFolder, $template);

            // Fallback to default page template if specific template doesn't exist
            if (! view()->exists($view)) {
                $view = sprintf('%s.page', $themeFolder);
            }

            $data = ['page' => $post];

            // Check if this is the blog listing page and add posts
            if ($readingSettings->post_page && $post->post_slug === $readingSettings->post_page) {
                $posts = Post::where('post_type', 'post')
                    ->where('post_status', 'published')
                    ->with(['author', 'categories', 'media'])
                    ->orderBy('post_published_at', 'desc')
                    ->paginate($readingSettings->posts_per_page ?? 10);

                $data['posts'] = $posts;
            }

            return view($view, $data);
        })->name($page['route_name']);
    }

    /**
     * Get pages with route names, cached for performance
     */
    protected function getCachedPages(): array
    {
        return Cache::remember('franken_cms_page_routes', now()->addDay(), function () {
            return Post::withoutGlobalScopes()
                ->where('post_type', 'page')
                ->whereNotNull('route_name')
                ->where('post_status', 'published')
                ->get()
                ->map(function ($page) {
                    return [
                        'id'         => $page->id,
                        'route_name' => $page->route_name,
                        'path'       => $page->getHierarchicalPath(),
                    ];
                })
                ->toArray();
        });
    }
}
