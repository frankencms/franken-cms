<?php

use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Registries\SettingsTabRegistry;
use FrankenCms\Services\SettingsTabService;

beforeEach(function () {
    $this->registry = new SettingsTabRegistry;
    $this->service = new SettingsTabService($this->registry);
});

it('can register default tabs', function () {
    $this->service->registerDefaultTabs();

    expect($this->registry->hasProvider('general'))->toBeTrue();
    expect($this->registry->hasProvider('reading'))->toBeTrue();
    expect($this->registry->hasProvider('media'))->toBeTrue();
    expect($this->registry->hasProvider('permalinks'))->toBeTrue();
});

it('can register a custom tab', function () {
    $provider = Mockery::mock(SettingsTabProviderInterface::class);
    $provider->shouldReceive('getTabKey')->andReturn('custom-tab');
    $provider->shouldReceive('getOrder')->andReturn(100);

    $this->service->registerTab($provider);

    expect($this->registry->hasProvider('custom-tab'))->toBeTrue();
    expect($this->registry->getProvider('custom-tab'))->toBe($provider);
});

it('returns the registry instance', function () {
    expect($this->service->getRegistry())->toBe($this->registry);
});

it('registers default tabs in correct order', function () {
    $this->service->registerDefaultTabs();

    $providers = $this->registry->getProviders();
    $keys = $providers->keys()->toArray();

    // Check that general tab comes first (order 10)
    expect($keys[0])->toBe('general');
    // Check that reading tab comes second (order 20)
    expect($keys[1])->toBe('reading');
});

it('can register custom tab alongside default tabs', function () {
    // Register custom tab with order 15 (between general and reading)
    $customProvider = Mockery::mock(SettingsTabProviderInterface::class);
    $customProvider->shouldReceive('getTabKey')->andReturn('custom-tab');
    $customProvider->shouldReceive('getOrder')->andReturn(15);

    $this->service->registerTab($customProvider);
    $this->service->registerDefaultTabs();

    $providers = $this->registry->getProviders();
    $keys = $providers->keys()->toArray();

    // Custom tab should appear between general (10) and reading (20)
    expect($keys[0])->toBe('general');
    expect($keys[1])->toBe('custom-tab');
    expect($keys[2])->toBe('reading');
});
