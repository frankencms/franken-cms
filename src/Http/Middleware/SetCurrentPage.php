<?php

declare(strict_types=1);

namespace FrankenCms\Http\Middleware;

use Closure;
use Exception;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentPage
{
    public function __construct(
        protected CurrentPageService $currentPageService,
        protected ContentResolver $contentResolver,
        protected ReadingSettings $settings
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');

        // Skip homepage and blog listing pages
        if ($this->isRootPath($path) || $this->isBlogListingPage($path) || $this->isTaxonomyArchivePath($path)) {
            return $next($request);
        }

        // Check if this is a specific post
        if ($this->contentResolver->isPostPath($path)) {
            $slug = $this->contentResolver->extractSlugFromPostPath($path);
            $post = $this->contentResolver->resolvePost($slug, $request->query('p'));
            $this->currentPageService->setPage($post);
        } else {
            // Attempt to resolve as a page
            try {
                $page = \FrankenCms\Models\Page::where('post_slug', $path)
                    ->where('post_status', 'published')
                    ->firstOrFail();
                $this->currentPageService->setPage($page);
            } catch (Exception $e) {
                // Page not found, let the controller handle it
            }
        }

        return $next($request);
    }

    private function isRootPath(string $path): bool
    {
        return $path === '' || $path === '/';
    }

    private function isBlogListingPage(string $path): bool
    {
        return $this->settings->post_page && $path === $this->settings->post_page;
    }

    private function isTaxonomyArchivePath(string $path): bool
    {
        $parts = explode('/', $path);

        if (count($parts) !== 2) {
            return false;
        }

        [$taxonomyName, $slug] = $parts;

        $taxonomy = \FrankenCms\Models\Taxonomy::where('name', $taxonomyName)->first();

        return $taxonomy !== null;
    }
}
