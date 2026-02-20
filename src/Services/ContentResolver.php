<?php

namespace FrankenCms\Services;

use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Settings\PermalinkSettings;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\View\View;

readonly class ContentResolver
{
    public function __construct(
        private ReadingSettings $readingSettings,
        private PermalinkSettings $permalinkSettings,
        private CurrentPageService $currentPageService
    ) {}

    public function resolveHomePage(): View
    {
        $homePage = $this->readingSettings->home_page;

        // If a specific homepage is set, use it
        if ($homePage) {
            // Eager load parent for potential breadcrumb support
            $page = Page::with('parent')
                ->where('post_slug', $homePage)
                ->where('post_status', PostStatus::PUBLISH)
                ->firstOrFail();
            return TemplateResolver::resolve($page);
        }

        // If no homepage is set, look for the theme's welcome/setup template
        $themeFolder = config('franken-cms.theme_folder');
        $welcomeView = sprintf('%s.welcome', $themeFolder);

        if (view()->exists($welcomeView)) {
            return view($welcomeView);
        }

        // If no welcome template exists, 404
        abort(404, 'No homepage configured. Please set a homepage in Settings > Reading.');
    }

    public function resolvePost(string $slug, ?string $queryId = null): ?Post
    {

        $post = match ($this->permalinkSettings->permalink_structure) {
            PermalinkStructure::POST_NAME->value      => $this->findPostBySlug($slug),
            PermalinkStructure::DAY_AND_NAME->value   => $this->findPostBySlug($this->getLastSegment($slug)),
            PermalinkStructure::MONTH_AND_NAME->value => $this->findPostBySlug($this->getLastSegment($slug)),
            PermalinkStructure::NUMERIC->value        => $this->findPostById($this->getLastSegment($slug)),
            PermalinkStructure::CUSTOM->value         => $this->findByCustomPermalink($slug),
            default                                   => null,
        };

        if (! $post) {
            abort(404);
        }

        app(PostService::class)->setPost($post);

        return $post;

    }

    public function resolvePage(string $path): View
    {
        // Reuse page already resolved by SetCurrentPage middleware
        $existingPage = $this->currentPageService->getPage();
        if ($existingPage
            && $existingPage->post_type === 'page'
            && $existingPage->post_slug === $path
            && ! str_contains($path, '/')
        ) {
            return TemplateResolver::resolve($existingPage);
        }

        // Handle hierarchical pages (e.g., /about/team)
        $segments = array_filter(explode('/', $path));

        if (count($segments) === 1) {
            // Simple single-level page
            // Eager load parent for breadcrumb support (prevents N+1 if ancestors() is called)
            $page = Page::withoutGlobalScopes()
                ->with('parent')
                ->where('post_slug', $path)
                ->where('post_status', PostStatus::PUBLISH)
                ->firstOrFail();
        } else {
            // Hierarchical page - traverse the path
            $page = $this->resolveHierarchicalPage($segments);
        }

        // Ensure the final page is published
        if ($page->post_status !== PostStatus::PUBLISH) {
            abort(404);
        }

        return TemplateResolver::resolve($page);
    }

    public function isPostPath(string $path): bool
    {
        $postPage = $this->readingSettings->post_page;

        return $postPage && ($path === $postPage || str_starts_with($path, $postPage . '/'));
    }

    public function extractSlugFromPostPath(string $path): string
    {
        return trim(substr($path, strlen($this->readingSettings->post_page) + 1), '/');
    }

    /**
     * Resolve a hierarchical page by traversing the path segments
     */
    private function resolveHierarchicalPage(array $segments): Page
    {
        $currentParentId = null;
        $currentPage = null;

        foreach ($segments as $slug) {
            $query = Page::withoutGlobalScopes()
                ->where('post_slug', $slug)
                ->where('post_status', PostStatus::PUBLISH);

            if ($currentParentId === null) {
                // First segment - find root page with no parent
                $query->whereNull('parent_id');
            } else {
                // Subsequent segments - find page with correct parent
                $query->where('parent_id', $currentParentId);
            }

            $currentPage = $query->first();

            if (! $currentPage) {
                abort(404, "Page not found in hierarchy: {$slug}");
            }

            $currentParentId = $currentPage->id;
        }

        return $currentPage;
    }

    private function findPostById(?string $id): ?Post
    {
        return $id ? Post::visibleOnFrontend()->find($id) : null;
    }

    private function findPostBySlug(string $slug): ?Post
    {
        return Post::with('parent')->visibleOnFrontend()->where('post_slug', $slug)->first();
    }

    private function findByCustomPermalink(string $slug): ?Post
    {
        $structure = $this->permalinkSettings->custom_permalink_structure;
        $segments = array_values(array_filter(explode('/', $slug)));

        // Return early if segments don't match structure length
        if (count($segments) !== count($structure)) {
            return null;
        }

        $query = Post::query()->visibleOnFrontend();

        // Build query based on permalink structure
        foreach ($structure as $index => $structureTag) {
            match ($structureTag) {
                '%postname%' => $query->where('post_slug', $segments[$index]),
                '%post_id%'  => $query->where('id', $segments[$index]),
                default      => null
            };
        }

        return $query->first();
    }

    private function getLastSegment(string $path): string
    {
        $segments = explode('/', $path);
        return end($segments);
    }
}
