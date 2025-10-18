<?php

declare(strict_types=1);

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Settings\RobotsSettings;

class RobotsSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTabKey(): string
    {
        return 'robots';
    }

    public function getTab(): Tab
    {
        return Tab::make('Robots.txt')
            ->icon('heroicon-o-bug-ant')
            ->schema([
                Section::make('Robots.txt Configuration')
                    ->description('Configure how search engine crawlers interact with your site.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable Dynamic Robots.txt')
                            ->helperText('Generate robots.txt dynamically. If disabled, only static robots.txt file will be used.')
                            ->default(true)
                            ->columnSpanFull(),

                        Repeater::make('user_agents')
                            ->label('User Agent Rules')
                            ->schema([
                                TextInput::make('user_agent')
                                    ->label('User Agent')
                                    ->helperText('Specify bot name (e.g., "Googlebot", "Bingbot") or use "*" for all bots')
                                    ->default('*')
                                    ->required(),

                                TagsInput::make('rules')
                                    ->label('Rules')
                                    ->helperText('Enter rules like "Disallow: /admin" or "Allow: /public" (press Enter after each)')
                                    ->placeholder('Example: Disallow: /admin')
                                    ->reorderable()
                                    ->required(),

                                TextInput::make('crawl_delay')
                                    ->label('Crawl Delay (seconds)')
                                    ->helperText('Optional delay between requests (leave empty for none)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->nullable(),
                            ])
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->addActionLabel('Add User Agent')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['user_agent'] ?? 'New User Agent'),

                        TagsInput::make('additional_sitemaps')
                            ->label('Additional Sitemaps')
                            ->helperText('Add custom sitemap URLs. Auto-generated sitemaps are added automatically.')
                            ->placeholder('/custom-sitemap.xml')
                            ->columnSpanFull(),

                        TextInput::make('host')
                            ->label('Canonical Host')
                            ->helperText('Optional: Specify the preferred domain (e.g., https://example.com)')
                            ->url()
                            ->placeholder('https://example.com')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return RobotsSettings::class;
    }

    public function getOrder(): int
    {
        return 60; // After SEO (50)
    }
}
