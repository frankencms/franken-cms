<?php

namespace FrankenCms\Contracts;

use FrankenCms\Models\Post;

interface CurrentPageInterface
{
    public function setPage(Post $page);

    public function getPage(): ?Post;
}
