<?php

namespace FrankenCms\Observers;

use FrankenCms\Models\Page;
use FrankenCms\Services\PageRouteService;

class PageObserver
{
    public function creating(Page $post): void
    {
        if (! $post->post_author_id) {
            $post->post_author_id = auth()->user()->id;
        }

        // Auto-fill route_name from slug if not provided
        if (empty($post->route_name) && ! empty($post->post_slug)) {
            $post->route_name = $post->post_slug;
        }
    }

    public function created(Page $post): void
    {
        // Clear route cache after page is created
        app(PageRouteService::class)->clearCache();
    }

    public function updating(Page $post): void
    {
        // If route_name is empty during update, fill it from slug
        // This handles cases where old pages didn't have route_name set
        if (empty($post->route_name) && ! empty($post->post_slug)) {
            $post->route_name = $post->post_slug;
        }
    }

    public function updated(Page $post): void
    {
        // Clear route cache when page is updated (route_name, slug, or status might change)
        if ($post->wasChanged(['route_name', 'post_slug', 'post_status', 'parent_id'])) {
            app(PageRouteService::class)->clearCache();
        }
    }

    public function deleted(Page $post): void
    {
        // Clear route cache when page is deleted
        app(PageRouteService::class)->clearCache();
    }
}
