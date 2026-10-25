<?php

use FrankenCms\Settings\SitemapSettings;
use Illuminate\Support\Facades\Route;

describe('sitemap-routes.xml endpoint', function () {
    test('serves discovered app routes as xml', function () {
        Route::middleware('web')->get('pricing', fn () => 'ok');

        $response = $this->get('/sitemap-routes.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        expect($response->getContent())->toContain(url('pricing'));
        expect($response->getContent())->toContain('<urlset');
    });

    test('serves a valid empty urlset when no routes qualify', function () {
        $response = $this->get('/sitemap-routes.xml');

        $response->assertOk();
        expect($response->getContent())->toContain('<urlset');
    });

    test('returns 404 when app routes sitemap is disabled', function () {
        $settings = app(SitemapSettings::class);
        $settings->include_app_routes = false;
        $settings->save();

        $this->get('/sitemap-routes.xml')->assertNotFound();
    });

    test('returns 404 when the sitemap system is disabled', function () {
        $settings = app(SitemapSettings::class);
        $settings->enabled = false;
        $settings->save();

        $this->get('/sitemap-routes.xml')->assertNotFound();
    });
});

describe('sitemap index entry', function () {
    test('index lists sitemap-routes.xml when enabled', function () {
        $this->get('/sitemap.xml')->assertOk()->assertSee('sitemap-routes.xml');
    });

    test('index omits sitemap-routes.xml when disabled', function () {
        $settings = app(SitemapSettings::class);
        $settings->include_app_routes = false;
        $settings->save();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        expect($response->getContent())->not->toContain('sitemap-routes.xml');
    });
});
