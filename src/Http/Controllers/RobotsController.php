<?php

declare(strict_types=1);

namespace FrankenCms\Http\Controllers;

use FrankenCms\Services\RobotsService;
use Illuminate\Http\Response;

class RobotsController
{
    public function __construct(
        protected RobotsService $robotsService
    ) {}

    /**
     * Display the robots.txt file
     * Uses static file if exists, otherwise generates dynamically
     */
    public function index(): Response
    {
        $content = $this->robotsService->getContent();

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
