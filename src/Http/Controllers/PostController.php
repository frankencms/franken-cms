<?php

namespace FrankenCms\Http\Controllers;

use FrankenCms\Models\Post;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Settings\CmsSettings;
use Illuminate\Http\Request;

class PostController
{
    public function __construct(
        private readonly ContentResolver $contentResolver,
        private readonly CmsSettings $settings
    ) {}

    public function index(Request $request)
    {

        $posts = Post::query()
            ->orderBy('post_published_at', 'desc')
            ->paginate($this->settings->posts_per_page);

        $templateFolder = config('franken-cms.template_folder');

        return view($templateFolder . '.post-index', compact('posts'));

    }
}
