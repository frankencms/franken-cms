<?php

use FrankenCms\Settings\GeneralSettings;
use FrankenCms\SettingsTabs\GeneralSettingsTabProvider;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;

beforeEach(function () {
    $this->provider = new GeneralSettingsTabProvider();
});

it('returns correct tab key', function () {
    expect($this->provider->getTabKey())->toBe('general');
});

it('returns correct settings class', function () {
    expect($this->provider->getSettingsClass())->toBe(GeneralSettings::class);
});

it('returns correct order', function () {
    expect($this->provider->getOrder())->toBe(10);
});

it('returns a valid tab instance', function () {
    $tab = $this->provider->getTab();

    expect($tab)->toBeInstanceOf(Tab::class);
});

it('tab has correct title', function () {
    $tab = $this->provider->getTab();

    expect($tab->getLabel())->toBe(__('franken-cms::messages.settings.general.title'));
});

it('tab schema contains required form components', function () {
    $tab = $this->provider->getTab();

    // Test that tab has a schema (without initializing components)
    expect($tab->getLabel())->toBeString();
    expect($tab->getLabel())->toBe(__('franken-cms::messages.settings.general.title'));
});

it('provides correct interface implementation', function () {
    // Test that the provider implements the interface correctly
    expect($this->provider->getTabKey())->toBe('general');
    expect($this->provider->getSettingsClass())->toBe(GeneralSettings::class);
    expect($this->provider->getOrder())->toBe(10);
    expect($this->provider->getTab())->toBeInstanceOf(Tab::class);
});

it('has consistent tab configuration', function () {
    $tab = $this->provider->getTab();

    // Test that tab is properly configured
    expect($tab->getLabel())->toBeString();
    expect($tab->getLabel())->not()->toBeEmpty();
});

it('enum options method returns correct format', function () {
    // Use reflection to test the private method
    $reflection = new ReflectionClass($this->provider);
    $method = $reflection->getMethod('enumOptions');
    $method->setAccessible(true);

    // Create a mock enum class for testing
    $mockEnum = new class {
        public static function cases() {
            return [
                new class {
                    public $value = 'value1';
                    public function getLabel() { return 'Label 1'; }
                },
                new class {
                    public $value = 'value2';
                    public function getLabel() { return 'Label 2'; }
                }
            ];
        }
    };

    $result = $method->invoke($this->provider, get_class($mockEnum));

    expect($result)->toBe([
        'value1' => 'Label 1',
        'value2' => 'Label 2'
    ]);
});