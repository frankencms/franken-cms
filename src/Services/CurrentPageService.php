<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\CurrentPageInterface;
use FrankenCms\Models\Post;

class CurrentPageService implements CurrentPageInterface
{
    protected ?Post $page = null;

    public function setPage(Post $page): void
    {
        $this->page = $page;
    }

    public function getPage(): ?Post
    {
        return $this->page;
    }
}
