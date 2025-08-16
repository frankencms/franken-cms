<?php

namespace FrankenCms\Contracts;

use FrankenCms\Models\Page;

interface CurrentPageInterface
{
    public function setPage(Page $page);

    public function getPage();
}
