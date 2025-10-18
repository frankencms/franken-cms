<?php

use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Registries\SettingsTabRegistry;

beforeEach(function () {
    $this->registry = new SettingsTabRegistry;
});

it('can register a settings tab provider', function () {
    $provider = Mockery::mock(SettingsTabProviderInterface::class);
    $provider->shouldReceive('getTabKey')->andReturn('test-tab');
    $provider->shouldReceive('getOrder')->andReturn(10);

    $this->registry->register($provider);

    expect($this->registry->hasProvider('test-tab'))->toBeTrue();
});

it('can retrieve a registered provider', function () {
    $provider = Mockery::mock(SettingsTabProviderInterface::class);
    $provider->shouldReceive('getTabKey')->andReturn('test-tab');
    $provider->shouldReceive('getOrder')->andReturn(10);

    $this->registry->register($provider);

    expect($this->registry->getProvider('test-tab'))->toBe($provider);
});

it('returns null for non-existent provider', function () {
    expect($this->registry->getProvider('non-existent'))->toBeNull();
});

it('orders providers by priority', function () {
    $provider1 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider1->shouldReceive('getTabKey')->andReturn('tab-1');
    $provider1->shouldReceive('getOrder')->andReturn(30);

    $provider2 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider2->shouldReceive('getTabKey')->andReturn('tab-2');
    $provider2->shouldReceive('getOrder')->andReturn(10);

    $provider3 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider3->shouldReceive('getTabKey')->andReturn('tab-3');
    $provider3->shouldReceive('getOrder')->andReturn(20);

    $this->registry->register($provider1);
    $this->registry->register($provider2);
    $this->registry->register($provider3);

    $providers = $this->registry->getProviders();

    expect($providers->keys()->toArray())->toBe(['tab-2', 'tab-3', 'tab-1']);
});

it('can get all tabs from providers', function () {
    $tab1 = Tab::make('Tab 1');
    $tab2 = Tab::make('Tab 2');

    $provider1 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider1->shouldReceive('getTabKey')->andReturn('tab-1');
    $provider1->shouldReceive('getOrder')->andReturn(10);
    $provider1->shouldReceive('getTab')->andReturn($tab1);

    $provider2 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider2->shouldReceive('getTabKey')->andReturn('tab-2');
    $provider2->shouldReceive('getOrder')->andReturn(20);
    $provider2->shouldReceive('getTab')->andReturn($tab2);

    $this->registry->register($provider1);
    $this->registry->register($provider2);

    $tabs = $this->registry->getTabs();

    expect($tabs)->toHaveCount(2);
    expect($tabs[0])->toBe($tab1);
    expect($tabs[1])->toBe($tab2);
});

it('can get all settings classes from providers', function () {
    $provider1 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider1->shouldReceive('getTabKey')->andReturn('tab-1');
    $provider1->shouldReceive('getOrder')->andReturn(10);
    $provider1->shouldReceive('getSettingsClass')->andReturn('App\\Settings\\FirstSettings');

    $provider2 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider2->shouldReceive('getTabKey')->andReturn('tab-2');
    $provider2->shouldReceive('getOrder')->andReturn(20);
    $provider2->shouldReceive('getSettingsClass')->andReturn('App\\Settings\\SecondSettings');

    $this->registry->register($provider1);
    $this->registry->register($provider2);

    $classes = $this->registry->getSettingsClasses();

    expect($classes)->toBe(['App\\Settings\\FirstSettings', 'App\\Settings\\SecondSettings']);
});

it('replaces provider with same key', function () {
    $provider1 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider1->shouldReceive('getTabKey')->andReturn('test-tab');
    $provider1->shouldReceive('getOrder')->andReturn(10);

    $provider2 = Mockery::mock(SettingsTabProviderInterface::class);
    $provider2->shouldReceive('getTabKey')->andReturn('test-tab');
    $provider2->shouldReceive('getOrder')->andReturn(20);

    $this->registry->register($provider1);
    $this->registry->register($provider2);

    expect($this->registry->getProviders())->toHaveCount(1);
    expect($this->registry->getProvider('test-tab'))->toBe($provider2);
});
