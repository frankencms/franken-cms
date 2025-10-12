<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\PostServiceInterface;
use FrankenCms\Models\Post;

class PostService implements PostServiceInterface
{
    protected ?Post $post = null;

    public function setPost(Post $post): void
    {
        $this->post = $post;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }
}
