<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\CurrentPageInterface;
use FrankenCms\Models\Page;

class CurrentPageService implements CurrentPageInterface
{
    protected ?Page $page = null;

    public function setPage(Page $page): void
    {
        $this->page = $page;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }
}
