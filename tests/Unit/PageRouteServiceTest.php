<?php

use FrankenCms\Services\PageRouteService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

it('catches QueryException during route registration', function () {
    // Simulate a QueryException (e.g., table doesn't exist during fresh install)
    Cache::shouldReceive('remember')
        ->once()
        ->andThrow(new QueryException('default', 'select * from posts', [], new \Exception('table not found')));

    $service = new PageRouteService;

    // Should not throw — QueryException is caught silently
    $service->registerPageRoutes();

    expect(true)->toBeTrue();
});

it('does not catch non-database exceptions during route registration', function () {
    // Simulate a RuntimeException (a real bug, not a missing table)
    Cache::shouldReceive('remember')
        ->once()
        ->andThrow(new RuntimeException('Something went wrong'));

    $service = new PageRouteService;

    $service->registerPageRoutes();
})->throws(RuntimeException::class, 'Something went wrong');
