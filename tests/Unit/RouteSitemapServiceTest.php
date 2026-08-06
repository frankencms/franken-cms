<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Services\RouteSitemapService;
use FrankenCms\Settings\SitemapSettings;
use FrankenCms\Tests\Support\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelSettings\Settings;

beforeEach(function () {
    // Posts require an author with id 1 (same setup as SitemapServiceTest)
    User::create([
        'id'       => 1,
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

function createRouteSitemapSettingsMock(): SitemapSettings
{
    $settings = Mockery::mock(SitemapSettings::class)->makePartial();

    $reflection = new ReflectionProperty(Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    $settings->enabled = true;
    $settings->default_change_frequency = 'weekly';
    $settings->default_priority = 0.5;
    $settings->max_urls_per_sitemap = 50000;
    $settings->excluded_paths = [];
    $settings->include_images = true;
    $settings->custom_sitemaps = [];
    $settings->include_app_routes = true;

    return $settings;
}

function routeSitemapService(?SitemapSettings $settings = null): RouteSitemapService
{
    return new RouteSitemapService(
        $settings ?? createRouteSitemapSettingsMock(),
        app('router'),
        app(PageRouteService::class)
    );
}

describe('Structural filters', function () {
    test('includes static GET web routes', function () {
        Route::middleware('web')->get('pricing', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->toContain(url('pricing'));
    });

    test('includes unnamed routes', function () {
        Route::middleware('web')->get('unnamed-page', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->toContain(url('unnamed-page'));
    });

    test('includes Route::view routes', function () {
        Route::view('view-page', 'welcome')->middleware('web');

        expect(routeSitemapService()->generate()->render())->toContain(url('view-page'));
    });

    test('excludes parameterized routes', function () {
        Route::middleware('web')->get('products/{id}', fn ($id) => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('products');
    });

    test('excludes non-GET routes', function () {
        Route::middleware('web')->post('contact-submit', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('contact-submit');
    });

    test('excludes routes without web middleware', function () {
        Route::get('api-like-route', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('api-like-route');
    });

    test('excludes domain-restricted routes', function () {
        Route::middleware('web')->domain('sub.example.com')->get('domain-page', fn () => 'ok');

        expect(routeSitemapService()->generate()->render())->not->toContain('domain-page');
    });
});

describe('Heuristic filters', function () {
    test('excludes routes behind auth middleware', function () {
        Route::middleware(['web', 'auth'])->get('dashboard-page', fn () => 'ok');
        Route::middleware(['web', 'auth:sanctum'])->get('sanctum-page', fn () => 'ok');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('dashboard-page');
        expect($xml)->not->toContain('sanctum-page');
    });

    test('excludes guest, verified, signed, and password.confirm routes', function () {
        Route::middleware(['web', 'guest'])->get('login-page', fn () => 'ok');
        Route::middleware(['web', 'verified'])->get('verified-page', fn () => 'ok');
        Route::middleware(['web', 'signed'])->get('signed-page', fn () => 'ok');
        Route::middleware(['web', 'password.confirm'])->get('confirm-page', fn () => 'ok');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('login-page');
        expect($xml)->not->toContain('verified-page');
        expect($xml)->not->toContain('signed-page');
        expect($xml)->not->toContain('confirm-page');
    });

    test('excludes vendor routes by name prefix', function () {
        Route::middleware('web')->get('vendor-a', fn () => 'ok')->name('filament.admin.pages.dashboard');
        Route::middleware('web')->get('vendor-b', fn () => 'ok')->name('livewire.update');
        Route::middleware('web')->get('vendor-c', fn () => 'ok')->name('horizon.index');

        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('vendor-a');
        expect($xml)->not->toContain('vendor-b');
        expect($xml)->not->toContain('vendor-c');
    });

    test('excludes package routes', function () {
        // robots.txt, feed, feed/atom, sitemap*.xml are registered by the
        // package's own routes/web.php which is loaded in tests
        $xml = routeSitemapService()->generate()->render();

        expect($xml)->not->toContain('robots.txt');
        expect($xml)->not->toContain('sitemap.xml');
        expect($xml)->not->toContain(url('feed'));
    });

    test('excludes CMS page routes registered by PageRouteService', function () {
        Cache::forget('franken_cms_page_routes');

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'landing',
            'route_name'  => 'my.landing',
        ]);

        Route::middleware('web')->get('landing', fn () => 'ok')->name('my.landing');

        expect(routeSitemapService()->generate()->render())->not->toContain(url('landing'));
    });

    test('excludes redirect routes', function () {
        Route::redirect('old-path', 'new-path')->middleware('web');

        expect(routeSitemapService()->generate()->render())->not->toContain('old-path');
    });
});
