<?php

namespace FrankenCms\Http\Controllers;

use FrankenCms\Models\Page;
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
}
