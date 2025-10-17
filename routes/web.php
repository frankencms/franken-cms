<?php

use FrankenCms\Http\Controllers\RouteController;
use FrankenCms\Services\PageRouteService;
use Illuminate\Support\Facades\Route;

// Apply web middleware group to all package routes
Route::middleware('web')->group(function () {
    // Register named routes for pages (must be before fallback)
    app(PageRouteService::class)->registerPageRoutes();

    // Catch-all route for FrankenCMS content (pages, posts, etc.)
    // This should be the last route registered
    Route::fallback([RouteController::class, 'index']);
});
