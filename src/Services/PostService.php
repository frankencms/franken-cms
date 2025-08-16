<?php

namespace FrankenCms\Services;

use FrankenCMS\FrankenCms\Contracts\PostServiceInterface;
use FrankenCMS\FrankenCms\Models\Post;

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
