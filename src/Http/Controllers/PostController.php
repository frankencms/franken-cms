<?php

namespace FrankenCms\Http\Controllers;

use FrankenCms\Models\Post;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Http\Request;

class PostController
{
    public function __construct(
        private readonly ContentResolver $contentResolver,
        private readonly ReadingSettings $settings
    ) {}

    public function index(Request $request)
    {

        $posts = Post::query()
            ->visibleOnFrontend()
            ->orderBy('post_published_at', 'desc')
            ->paginate($this->settings->posts_per_page);

        $themeFolder = config('franken-cms.theme_folder');

        return view($themeFolder . '.post-index', compact('posts'));

    }
}
