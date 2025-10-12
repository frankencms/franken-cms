<?php

namespace FrankenCms\Http\Controllers;

use FrankenCms\Models\Page;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Services\RouteHandler;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Http\Request;

class RouteController
{
    public function __construct(
        private readonly RouteHandler $routeHandler,
        private readonly ContentResolver $contentResolver,
        private readonly ReadingSettings $settings
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

        // Check if this path is the homepage slug - redirect to root
        if ($this->settings->home_page && $path === $this->settings->home_page) {
            return redirect('/');
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

        $themeFolder = config('franken-cms.theme_folder');

        // Use the page's template if it exists, otherwise default to 'page-blog'
        $template = $page?->template ?? 'page-blog';
        $view = sprintf('%s.%s', $themeFolder, $template);

        // Fallback to page-blog if the specific template doesn't exist
        if (! view()->exists($view)) {
            $view = sprintf('%s.page-blog', $themeFolder);
        }

        // Get posts for the listing
        $posts = \FrankenCms\Models\Post::where('post_type', 'post')
            ->where('post_status', 'published')
            ->with(['author', 'categories', 'media'])
            ->orderBy('post_published_at', 'desc')
            ->paginate($this->settings->posts_per_page ?? 10);

        return view($view, compact('posts', 'page'));
    }

    private function handlePostPath(string $path, Request $request)
    {
        $slug = $this->contentResolver->extractSlugFromPostPath($path);
        $post = $this->contentResolver->resolvePost($slug, $request->query('p'));

        $themeFolder = config('franken-cms.theme_folder');
        $template = $post->template ?? 'post';
        $view = sprintf('%s.%s', $themeFolder, $template);

        // Fallback to default post template if specific template doesn't exist
        if (! view()->exists($view)) {
            $view = sprintf('%s.post', $themeFolder);
        }

        return view($view, compact('post'));
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
            ->where('post_status', 'published')
            ->with(['author', 'categories', 'media'])
            ->orderBy('post_published_at', 'desc')
            ->paginate($this->settings->posts_per_page ?? 10);

        $themeFolder = config('franken-cms.theme_folder');

        // Try taxonomy-specific template first (e.g., 'archive-category', 'archive-tag')
        $specificView = sprintf('%s.archive-%s', $themeFolder, $taxonomyName);

        // Then try generic archive template
        $genericView = sprintf('%s.archive', $themeFolder);

        // Determine which view to use
        $view = view()->exists($specificView) ? $specificView : $genericView;

        return view($view, compact('term', 'taxonomy', 'posts'));
    }
}
