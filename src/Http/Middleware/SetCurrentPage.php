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

        // Handle homepage
        if ($this->isRootPath($path)) {
            $this->setHomePage();

            return $next($request);
        }

        // Handle blog listing page
        if ($this->isBlogListingPage($path)) {
            $this->setBlogPage();

            return $next($request);
        }

        // Handle taxonomy archives
        if ($this->isTaxonomyArchivePath($path)) {
            // Taxonomy archives don't set a page since they're not associated with a specific page
            return $next($request);
        }

        // Check if this is a specific post
        if ($this->contentResolver->isPostPath($path)) {
            $slug = $this->contentResolver->extractSlugFromPostPath($path);
            $post = $this->contentResolver->resolvePost($slug, $request->query('p'));
            $this->currentPageService->setPage($post);
        } else {
            // Attempt to resolve as a page (handles both simple and hierarchical pages)
            try {
                $segments = array_filter(explode('/', $path));

                if (count($segments) === 1) {
                    // Simple single-level page
                    $page = \FrankenCms\Models\Page::where('post_slug', $path)
                        ->where('post_status', 'published')
                        ->first();
                } else {
                    // Hierarchical page - traverse the path
                    $page = $this->resolveHierarchicalPage($segments);
                }

                if ($page) {
                    $this->currentPageService->setPage($page);
                }
            } catch (Exception $e) {
                // Page not found, let the controller handle it
            }
        }

        return $next($request);
    }

    private function setHomePage(): void
    {
        $homePageSlug = $this->settings->home_page;

        if (! $homePageSlug) {
            return;
        }

        try {
            $homePage = \FrankenCms\Models\Page::where('post_slug', $homePageSlug)
                ->where('post_status', 'published')
                ->first();

            if ($homePage) {
                $this->currentPageService->setPage($homePage);
            }
        } catch (Exception $e) {
            // Homepage not found
        }
    }

    private function setBlogPage(): void
    {
        $blogPageSlug = $this->settings->post_page;

        if (! $blogPageSlug) {
            return;
        }

        try {
            $blogPage = \FrankenCms\Models\Page::where('post_slug', $blogPageSlug)
                ->where('post_status', 'published')
                ->first();

            if ($blogPage) {
                $this->currentPageService->setPage($blogPage);
            }
        } catch (Exception $e) {
            // Blog page not found
        }
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

    /**
     * Resolve a hierarchical page by traversing the path segments
     */
    private function resolveHierarchicalPage(array $segments): ?\FrankenCms\Models\Page
    {
        $currentParentId = null;
        $currentPage = null;

        foreach ($segments as $slug) {
            $query = \FrankenCms\Models\Page::withoutGlobalScopes()->where('post_slug', $slug);

            if ($currentParentId === null) {
                // First segment - find root page with no parent
                $query->whereNull('parent_id');
            } else {
                // Subsequent segments - find page with correct parent
                $query->where('parent_id', $currentParentId);
            }

            $currentPage = $query->first();

            if (! $currentPage) {
                return null;
            }

            $currentParentId = $currentPage->id;
        }

        // Check if the final page is published
        if ($currentPage && $currentPage->post_status->value === 'published') {
            return $currentPage;
        }

        return null;
    }
}
