<?php

namespace FrankenCms\Http\Controllers;

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\RouteHandler;
use FrankenCms\Services\TemplateResolver;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Http\Request;

class RouteController
{
    public function __construct(
        private readonly RouteHandler $routeHandler,
        private readonly ContentResolver $contentResolver,
        private readonly ReadingSettings $settings,
        private readonly CurrentPageService $currentPageService
    ) {}

    public function index(Request $request)
    {

        // If there's a registered route, let Laravel handle it
        // TODO: check if this is even necessary due to fallback route
        //        if (! $this->routeHandler->shouldHandleRoute($request)) {
        //            return;
        //        }

        $path = trim($request->path(), '/');

        if ($this->isRootPath($path)) {
            return $this->contentResolver->resolveHomePage();
        }

        // Check if this path is the homepage - redirect to root
        if ($this->settings->home_page) {
            $homePage = Page::where('post_slug', $this->settings->home_page)->first();
            if ($homePage && '/' . $path === $homePage->getHierarchicalPath()) {
                return redirect('/');
            }
        }

        // Check if this is the blog listing page (post_page without a slug)
        if ($this->settings->post_page && $path === $this->settings->post_page) {
            return $this->handleBlogListingPage();
        }

        // Check if this is a taxonomy archive (category/slug or tag/slug)
        if ($this->isTaxonomyArchivePath($path)) {
            return $this->handleTaxonomyArchive($path);
        }

        // Check if this is a specific post (post_page with a slug)
        if ($this->contentResolver->isPostPath($path)) {
            return $this->handlePostPath($path, $request);
        }

        // Attempt to resolve as a page
        return $this->contentResolver->resolvePage($path);

    }

    private function handleBlogListingPage()
    {
        // Get the page set as the post_page to determine its template
        $postPageSlug = $this->settings->post_page;
        $page = Page::where('post_slug', $postPageSlug)->first();

        // Use the page's template if it exists, otherwise default to 'page-blog'
        $template = $page?->template ?? 'page-blog';

        // Livewire page components query (and paginate) their own posts
        if (TemplateResolver::isLivewireTemplate($template)) {
            return TemplateResolver::render($template, compact('page'));
        }

        // Get posts for the listing
        $posts = Post::query()
            ->visibleOnFrontend()
            ->with(['author', 'categories', 'media'])
            ->orderBy('post_published_at', 'desc')
            ->paginate($this->settings->posts_per_page ?? 10);

        // Fallback to page-blog if the specific template doesn't exist
        return TemplateResolver::render($template, compact('posts', 'page'), 'page-blog');
    }

    private function handlePostPath(string $path, Request $request)
    {
        $post = $this->currentPageService->getPage();

        if (! $post) {
            $slug = $this->contentResolver->extractSlugFromPostPath($path);
            $post = $this->contentResolver->resolvePost($slug, $request->query('p'));
        }

        // Fallback to default post template if specific template doesn't exist
        return TemplateResolver::render($post->template ?? 'post', compact('post'), 'post');
    }

    private function isRootPath(string $path): bool
    {
        return $path === '' || $path === '/';
    }

    private function isTaxonomyArchivePath(string $path): bool
    {
        // Check if path matches {taxonomy}/{slug} pattern
        $parts = explode('/', $path);

        if (count($parts) !== 2) {
            return false;
        }

        [$taxonomyName, $slug] = $parts;

        // Check if the taxonomy exists
        $taxonomy = Taxonomy::where('name', $taxonomyName)->first();

        return $taxonomy !== null;
    }

    private function handleTaxonomyArchive(string $path)
    {
        [$taxonomyName, $slug] = explode('/', $path);

        // Find the taxonomy and term
        $taxonomy = Taxonomy::where('name', $taxonomyName)->firstOrFail();
        $term = Term::where('taxonomy_id', $taxonomy->id)
            ->where('slug', $slug)
            ->firstOrFail();

        // Get posts that have this term
        $posts = $term->posts()
            ->where('post_status', PostStatus::PUBLISH)
            ->where('post_published_at', '<=', now())
            ->with(['author', 'categories', 'media'])
            ->orderBy('post_published_at', 'desc')
            ->paginate($this->settings->posts_per_page ?? 10);

        // Try taxonomy-specific template first (e.g., 'archive-category', 'archive-tag'),
        // then the generic archive template
        return TemplateResolver::render("archive-{$taxonomyName}", compact('term', 'taxonomy', 'posts'), 'archive');
    }
}
