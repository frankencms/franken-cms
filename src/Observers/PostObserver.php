<?php

namespace FrankenCms\Observers;

use FrankenCms\Models\Post;

class PostObserver
{
    public function creating(Post $post)
    {
        if (! $post->post_author_id) { // Check if the author ID is not provided
            $post->post_author_id = Auth::id(); // Set it to the currently authenticated user ID
        }
    }
}
