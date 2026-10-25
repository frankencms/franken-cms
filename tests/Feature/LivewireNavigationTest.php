<?php

use FrankenCms\Support\LivewireNavigation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\View\ComponentAttributeBag;

beforeEach(function () {
    Config::set('franken-cms.livewire.navigate', true);
    Config::set('franken-cms.livewire.navigate_prefetch', false);
});

it('returns nothing when navigation is disabled', function () {
    Config::set('franken-cms.livewire.navigate', false);

    expect(franken_navigate('/about'))->toBe('');
});

it('adds wire:navigate to internal links', function (string $url) {
    expect(franken_navigate($url))->toBe('wire:navigate');
})->with([
    'root'          => '/',
    'relative'      => '/about',
    'nested'        => '/company/team',
    'query string'  => '/blog?page=2',
    'same host url' => fn () => url('/about'),
]);

it('uses the hover modifier when prefetch is enabled', function () {
    Config::set('franken-cms.livewire.navigate_prefetch', true);

    expect(franken_navigate('/about'))->toBe('wire:navigate.hover');
});

it('skips links that need a full page load', function (?string $url) {
    expect(LivewireNavigation::isNavigable($url))->toBeFalse()
        ->and(franken_navigate($url))->toBe('');
})->with([
    'null'          => null,
    'empty'         => '',
    'anchor'        => '#content',
    'external'      => 'https://github.com/frankencms/franken-cms',
    'protocol rel.' => '//example.org/about',
    'mailto'        => 'mailto:hi@example.com',
    'tel'           => 'tel:+15555555555',
    'file'          => '/sitemap.xml',
    'upload'        => '/storage/report.pdf',
    'feed'          => '/feed',
    'atom feed'     => '/feed/atom',
    'admin'         => '/admin/page/pages',
]);

it('skips links that open in a new tab', function () {
    expect(franken_navigate('/about', '_blank'))->toBe('')
        ->and(franken_navigate('/about', '_self'))->toBe('wire:navigate');
});

it('honours custom except patterns', function () {
    Config::set('franken-cms.livewire.navigate_except', ['shop/*']);

    expect(franken_navigate('/shop/cart'))->toBe('')
        ->and(franken_navigate('/feed'))->toBe('wire:navigate');
});

it('renders the blade directive', function () {
    $html = Blade::render('<a href="/about" @frankenNavigate(\'/about\')>About</a>');

    expect($html)->toBe('<a href="/about" wire:navigate>About</a>');

    Config::set('franken-cms.livewire.navigate', false);

    expect(Blade::render('<a href="/about" @frankenNavigate(\'/about\')>About</a>'))
        ->toBe('<a href="/about" >About</a>');
});

it('adds wire:navigate to breadcrumb links only when enabled', function () {
    $breadcrumbs = [
        (object) ['label' => 'Home', 'url' => '/'],
        (object) ['label' => 'About', 'url' => null],
    ];

    $html = view('franken-cms::components.breadcrumbs', ['breadcrumbs' => $breadcrumbs, 'attributes' => new ComponentAttributeBag])->render();
    expect($html)->toContain('wire:navigate');

    Config::set('franken-cms.livewire.navigate', false);

    $html = view('franken-cms::components.breadcrumbs', ['breadcrumbs' => $breadcrumbs, 'attributes' => new ComponentAttributeBag])->render();
    expect($html)->not->toContain('wire:navigate');
});

it('adds wire:navigate to theme menu items only for same-tab internal links', function () {
    $render = fn (array $item) => view()->file(
        __DIR__ . '/../../stubs/theme/components/menu-item.blade.php',
        ['item' => $item, 'attributes' => new ComponentAttributeBag]
    )->render();

    expect($render(['url' => '/about', 'label' => 'About']))->toContain('wire:navigate')
        ->and($render(['url' => '/about', 'label' => 'About', 'target' => '_blank']))->not->toContain('wire:navigate')
        ->and($render(['url' => 'https://example.org', 'label' => 'Ext']))->not->toContain('wire:navigate');
});
