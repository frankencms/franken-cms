<?php

namespace YourPackage\SettingsTabs;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use YourPackage\Settings\YourPackageSettings;

/**
 * Example of how an external package can register its own settings tab
 *
 * To use this, create this class in your package and register it in your service provider:
 *
 * public function boot(): void
 * {
 *     $settingsTabService = $this->app->make(\FrankenCms\Services\SettingsTabService::class);
 *     $settingsTabService->registerTab(new YourPackageSettingsTabProvider());
 * }
 */
class ExamplePackageSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Your Package')
            ->schema([
                Section::make('Your Package Settings')
                    ->description('Configure your custom package settings')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->inlineLabel()
                            ->required()
                            ->password()
                            ->columnSpan(1),

                        TextInput::make('api_endpoint')
                            ->label('API Endpoint')
                            ->inlineLabel()
                            ->url()
                            ->default('https://api.yourservice.com')
                            ->required()
                            ->columnSpan(1),

                        Toggle::make('enabled')
                            ->label('Enable Integration')
                            ->inlineLabel()
                            ->default(true)
                            ->columnSpan(1),

                        TextInput::make('cache_duration')
                            ->label('Cache Duration (minutes)')
                            ->inlineLabel()
                            ->numeric()
                            ->default(60)
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return YourPackageSettings::class;
    }

    public function getOrder(): int
    {
        // Higher numbers appear later in the tab order
        return 100;
    }

    public function getTabKey(): string
    {
        return 'your-package';
    }
}

/**
 * Example Settings class that would accompany the tab provider above
 */
class YourPackageSettings extends \Spatie\LaravelSettings\Settings
{
    public ?string $api_key = null;
    public string $api_endpoint = 'https://api.yourservice.com';
    public bool $enabled = true;
    public int $cache_duration = 60;

    public static function group(): string
    {
        return 'your_package';  // Use a unique prefix to avoid conflicts
    }
}