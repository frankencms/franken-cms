<?php

use Illuminate\Support\Facades\Route;

describe('Route sitemap macros', function () {
    test('sitemap macro stores include metadata on the route action', function () {
        $route = Route::get('/macro-test', fn () => 'ok')->sitemap(0.9, 'monthly');

        expect($route->getAction('sitemap'))->toBe([
            'include'          => true,
            'priority'         => 0.9,
            'change_frequency' => 'monthly',
        ]);
    });

    test('sitemap macro defaults to null metadata', function () {
        $route = Route::get('/macro-default-test', fn () => 'ok')->sitemap();

        expect($route->getAction('sitemap'))->toBe([
            'include'          => true,
            'priority'         => null,
            'change_frequency' => null,
        ]);
    });

    test('skipSitemap macro stores exclusion on the route action', function () {
        $route = Route::get('/macro-skip-test', fn () => 'ok')->skipSitemap();

        expect($route->getAction('sitemap'))->toBe(['include' => false]);
    });

    test('macros are chainable', function () {
        $route = Route::get('/macro-chain-test', fn () => 'ok')->sitemap()->name('chain.test');

        expect($route->getName())->toBe('chain.test');
    });
});
