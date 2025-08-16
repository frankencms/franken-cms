<?php

namespace FrankenCms\Http\Controllers;

use FrankenCms\Models\Page;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Services\RouteHandler;
use FrankenCms\Settings\CmsSettings;
use Illuminate\Http\Request;

class RouteController
{
    public function __construct(
        private readonly RouteHandler $routeHandler,
        private readonly ContentResolver $contentResolver,
        private readonly CmsSettings $settings
    ) {}

    public function index(Request $request)
    {

        // If there's a registered route, let Laravel handle it
        // TODO: check if this is even necessary due to fallback route
        //        if (! $this->routeHandler->shouldHandleRoute($request)) {
        //            return;
        //        }

        $path = trim($request->path(), '/');

        if ($this->isRootPath($path)) {
            return $this->contentResolver->resolveHomePage();
        }

        if ($this->contentResolver->isPostPath($path)) {

            return $this->handlePostPath($path, $request);
        }

        // Attempt to resolve as a page
        return $this->contentResolver->resolvePage($path);

    }

    private function handlePostPath(string $path, Request $request)
    {
        $slug = $this->contentResolver->extractSlugFromPostPath($path);
        $post = $this->contentResolver->resolvePost($slug, $request->query('p'));

        // TODO: template folder settings

        return view('page-templates.post', compact('post'));

        //        return view('franken-cms::post.show', compact('post'));
    }

    private function isRootPath(string $path): bool
    {
        return $path === '' || $path === '/';
    }
}
