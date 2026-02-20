<?php

namespace FrankenCms\View\Components;

use FrankenCms\Models\Post;
use FrankenCms\Services\PostService;
use Illuminate\View\Component;

class CmsPost extends Component
{
    public ?Post $post;

    public function __construct(protected PostService $postService)
    {

        $this->post = $this->postService->getPost();
    }

    public function render()
    {
        return view('franken-cms::components.cms-post');
    }
}
