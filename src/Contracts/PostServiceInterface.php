<?php

namespace FrankenCms\Contracts;

use FrankenCms\Models\Post;

interface PostServiceInterface
{
    public function setPost(Post $post);

    public function getPost();
}
