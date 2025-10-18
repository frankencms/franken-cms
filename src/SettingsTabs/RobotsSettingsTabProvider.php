<?php

declare(strict_types=1);

namespace FrankenCms\SettingsTabs;

use Closure;
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
                            ->live()
                            ->columnSpanFull(),

                        Toggle::make('discourage_indexing')
                            ->label('Discourage Search Engine Indexing')
                            ->helperText('Block ALL search engines from indexing your entire site. Useful during development to prevent work-in-progress content from being indexed. When enabled, this overrides all other robot rules below.')
                            ->default(false)
                            ->live()
                            ->visible(fn ($get) => $get('enabled'))
                            ->columnSpanFull(),

                        Repeater::make('user_agents')
                            ->label('User Agent Rules')
                            ->visible(fn ($get) => $get('enabled') && ! $get('discourage_indexing'))
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
                                    ->required()
                                    ->rule(function () {
                                        return function (string $attribute, $value, Closure $fail) {
                                            if (! is_array($value)) {
                                                return;
                                            }

                                            foreach ($value as $rule) {
                                                // Check if the rule starts with Allow: or Disallow:
                                                if (! preg_match('/^(allow|disallow)\s*:/i', trim($rule))) {
                                                    $fail("Each rule must start with 'Allow:' or 'Disallow:'. Invalid rule: {$rule}");
                                                }
                                            }
                                        };
                                    }),

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
