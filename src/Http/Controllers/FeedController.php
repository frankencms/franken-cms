<?php

declare(strict_types=1);

namespace FrankenCms\Http\Controllers;

use FrankenCms\Services\FeedService;
use Illuminate\Http\Response;

class FeedController
{
    public function __construct(
        protected FeedService $feedService
    ) {}

    /**
     * Generate RSS 2.0 feed
     */
    public function rss(): Response
    {
        if (! $this->feedService->isEnabled()) {
            abort(404);
        }

        $feed = $this->feedService->generateRss();

        return response($feed, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }

    /**
     * Generate Atom 1.0 feed
     */
    public function atom(): Response
    {
        if (! $this->feedService->isEnabled()) {
            abort(404);
        }

        $feed = $this->feedService->generateAtom();

        return response($feed, 200, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
        ]);
    }
}
