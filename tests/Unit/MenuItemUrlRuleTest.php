<?php

use FrankenCms\Models\Menu;
use FrankenCms\Models\MenuItem;
use FrankenCms\Rules\MenuItemUrl;
use Illuminate\Support\Facades\Validator;

function validateMenuItemUrl(mixed $value): bool
{
    return Validator::make(['url' => $value], ['url' => new MenuItemUrl])->passes();
}

describe('MenuItemUrl rule', function () {

    it('accepts absolute URLs', function (string $url) {
        expect(validateMenuItemUrl($url))->toBeTrue();
    })->with([
        'https://example.com',
        'https://example.com/path?query=1',
        'http://example.com',
    ]);

    it('accepts root-relative paths', function (string $url) {
        expect(validateMenuItemUrl($url))->toBeTrue();
    })->with([
        '/',
        '/about',
        '/blog/my-post?page=2#comments',
    ]);

    it('accepts anchor links', function () {
        expect(validateMenuItemUrl('#contact'))->toBeTrue();
    });

    it('accepts mailto links', function () {
        expect(validateMenuItemUrl('mailto:igor@castle.test'))->toBeTrue();
    });

    it('accepts tel links', function () {
        expect(validateMenuItemUrl('tel:+15551234567'))->toBeTrue();
    });

    it('rejects invalid values', function (string $url) {
        expect(validateMenuItemUrl($url))->toBeFalse();
    })->with([
        'bare domain'          => 'example.com',
        'plain text'           => 'not a url',
        'javascript scheme'    => 'javascript:alert(1)',
        'empty mailto'         => 'mailto:',
        'mailto without email' => 'mailto:not-an-email',
        'empty tel'            => 'tel:',
        'bare hash'            => '#',
    ]);
});

describe('MenuItem custom URL output', function () {

    function makeMenuItemWithUrl(string $url): MenuItem
    {
        $menu = Menu::create([
            'name'      => 'Test Menu',
            'slug'      => 'test-menu-' . uniqid(),
            'is_active' => true,
        ]);

        return MenuItem::create([
            'menu_id'    => $menu->id,
            'label'      => 'Test Item',
            'url'        => $url,
            'sort_order' => 1,
            'is_active'  => true,
        ]);
    }

    it('fully qualifies relative URLs', function () {
        expect(makeMenuItemWithUrl('/about')->getUrl())->toBe(url('/about'));
    });

    it('passes absolute URLs through unchanged', function () {
        expect(makeMenuItemWithUrl('https://example.com/page')->getUrl())->toBe('https://example.com/page');
    });

    it('passes anchor, mailto and tel links through unchanged', function (string $url) {
        expect(makeMenuItemWithUrl($url)->getUrl())->toBe($url);
    })->with([
        '#contact',
        'mailto:igor@castle.test',
        'tel:+15551234567',
    ]);
});
