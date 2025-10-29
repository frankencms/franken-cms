<?php

namespace FrankenCms\Observers;

use FrankenCms\Models\Page;

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

    public function updating(Page $post): void
    {
        // If route_name is empty during update, fill it from slug
        // This handles cases where old pages didn't have route_name set
        if (empty($post->route_name) && ! empty($post->post_slug)) {
            $post->route_name = $post->post_slug;
        }
    }
}
