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
    }
}
