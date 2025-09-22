<?php

use FrankenCms\Helpers\PostHelper;
use FrankenCms\Http\Controllers\PostController;
use FrankenCms\Http\Controllers\RouteController;

Route::get(PostHelper::index_page(), [PostController::class, 'index'])
    ->name('post.index');

Route::get('{any}', [RouteController::class, 'index'])
    ->where('any', '.*')
    ->name('route.handler')
    ->fallback();
