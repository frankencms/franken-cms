<?php

namespace FrankenCms\Observers;

use FrankenCms\Models\Post;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Services\SitemapService;
use Illuminate\Support\Facades\Auth;

class PostObserver
{
    public function creating(Post $post)
    {
        if (! $post->post_author_id) { // Check if the author ID is not provided
            $post->post_author_id = Auth::id(); // Set it to the currently authenticated user ID
        }
    }

    /**
     * Handle the Post "saved" event.
     * Clear route cache when a page with route_name is saved.
     * Clear sitemap cache when any post or page is saved.
     */
    public function saved(Post $post)
    {
        if ($post->post_type === 'page' && $post->route_name) {
            app(PageRouteService::class)->clearCache();
        }

        // Clear sitemap cache for posts and pages
        if (in_array($post->post_type, ['post', 'page'])) {
            app(SitemapService::class)->clearCache();
        }
    }

    /**
     * Handle the Post "deleted" event.
     * Clear route cache when a page with route_name is deleted.
     * Clear sitemap cache when any post or page is deleted.
     */
    public function deleted(Post $post)
    {
        if ($post->post_type === 'page' && $post->route_name) {
            app(PageRouteService::class)->clearCache();
        }

        // Clear sitemap cache for posts and pages
        if (in_array($post->post_type, ['post', 'page'])) {
            app(SitemapService::class)->clearCache();
        }
    }
}
