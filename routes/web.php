<?php

use FrankenCms\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

// Catch-all route for FrankenCMS content (pages, posts, etc.)
// This should be the last route registered
Route::fallback([RouteController::class, 'index']);
