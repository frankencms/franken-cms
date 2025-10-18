<?php

use FrankenCms\Http\Controllers\FeedController;
use FrankenCms\Http\Controllers\RobotsController;
use FrankenCms\Http\Controllers\RouteController;
use FrankenCms\Http\Controllers\SitemapController;
use FrankenCms\Services\PageRouteService;
use Illuminate\Support\Facades\Route;

// Apply web middleware group to all package routes
Route::middleware('web')->group(function () {
    // Robots.txt route (static file takes precedence if exists)
    Route::get('robots.txt', [RobotsController::class, 'index'])->name('robots');

    // Feed routes
    Route::get('feed', [FeedController::class, 'rss'])->name('feed.rss');
    Route::get('feed/atom', [FeedController::class, 'atom'])->name('feed.atom');

    // Sitemap routes
    Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
    Route::get('sitemap-pages.xml', fn () => app(SitemapController::class)->postType('page'))->name('sitemap.pages');
    Route::get('sitemap-posts.xml', fn () => app(SitemapController::class)->postType('post'))->name('sitemap.posts');

    // Register named routes for pages (must be before fallback)
    app(PageRouteService::class)->registerPageRoutes();

    // Catch-all route for FrankenCMS content (pages, posts, etc.)
    // This should be the last route registered
    Route::fallback([RouteController::class, 'index']);
});
