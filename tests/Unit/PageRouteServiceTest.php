<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Tests\Support\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    User::create([
        'id'       => 1,
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

afterEach(function () {
    Post::query()->delete();
});

it('catches QueryException during route registration', function () {
    // Simulate a QueryException (e.g., table doesn't exist during fresh install)
    Cache::shouldReceive('remember')
        ->once()
        ->andThrow(new QueryException('default', 'select * from posts', [], new Exception('table not found')));

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

describe('Registered route names', function () {
    test('returns route names of published pages that have one', function () {
        Cache::forget('franken_cms_page_routes');

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'landing',
            'route_name'  => 'my.landing',
        ]);

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'no-name-page',
            'route_name'  => null,
        ]);

        $names = app(PageRouteService::class)->getRegisteredRouteNames();

        expect($names)->toBe(['my.landing']);
    });

    test('returns empty array when no pages have route names', function () {
        Cache::forget('franken_cms_page_routes');

        $names = app(PageRouteService::class)->getRegisteredRouteNames();

        expect($names)->toBe([]);
    });
});
