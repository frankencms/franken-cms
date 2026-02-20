<?php

declare(strict_types=1);

namespace FrankenCms\Http\Controllers;

use FrankenCms\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController
{
    public function __construct(
        protected SitemapService $sitemapService
    ) {}

    /**
     * Display the sitemap index file
     */
    public function index(): Response
    {
        if (! $this->sitemapService->isEnabled()) {
            abort(404);
        }

        $content = $this->sitemapService->render();

        return response($content)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * Display sitemap for a specific post type
     */
    public function postType(string $postType): Response
    {
        if (! $this->sitemapService->isEnabled()) {
            abort(404);
        }

        $sitemap = $this->sitemapService->generateForPostType($postType);

        return response($sitemap->render())
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
