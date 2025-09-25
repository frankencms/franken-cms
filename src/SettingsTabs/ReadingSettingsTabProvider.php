<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Models\Page;
use FrankenCms\Settings\ReadingSettings;

class ReadingSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Reading')
            ->columns(3)
            ->schema([
                Section::make(__('franken-cms::messages.settings.reading.title'))
                    ->description(__('franken-cms::messages.settings.reading.description'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Radio::make('homepage_displays')
                            ->live()
                            ->inlineLabel()
                            ->label('Your Homepage Displays')
                            ->options([
                                'latest_posts' => 'Your latest posts',
                                'static_page'  => 'A static page (select below)',
                            ])
                            ->default('latest_posts')
                            ->required()
                            ->columnSpan(2),

                        Select::make('home_page')
                            ->label('Homepage')
                            ->inlineLabel()
                            ->required(fn (Get $get) => $get('homepage_displays') === 'static_page')
                            ->visible(fn (Get $get) => $get('homepage_displays') === 'static_page')
                            ->options(
                                Page::query()->pluck('post_title', 'post_slug')
                            )
                            ->searchable()
                            ->columnSpan(2),

                        Select::make('post_page')
                            ->label('Posts Page')
                            ->inlineLabel()
                            ->required(fn (Get $get) => $get('homepage_displays') === 'static_page')
                            ->visible(fn (Get $get) => $get('homepage_displays') === 'static_page')
                            ->options(
                                Page::query()->pluck('post_title', 'post_slug')->toArray()
                            )
                            ->searchable()
                            ->columnSpan(2),

                        TextInput::make('posts_per_page')
                            ->label('Blog Pages Show At Most')
                            ->postfix('posts')
                            ->inlineLabel()
                            ->default(10)
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('syndicate_feeds')
                            ->label('Syndicate Feeds Show The Most Recent')
                            ->postfix('items')
                            ->inlineLabel()
                            ->default(10)
                            ->required()
                            ->columnSpan(2),

                        Radio::make('include_in_feed')
                            ->inlineLabel()
                            ->label('For Each Article In A Feed, Include')
                            ->helperText('Your theme determines how content is displayed in browsers.')
                            ->options([
                                'full_text' => 'Full Text',
                                'summary'   => 'Summary',
                            ])
                            ->default('full_text')
                            ->required()
                            ->columnSpan(2),

                        Checkbox::make('discourage_search_visibility')
                            ->inlineLabel()
                            ->label('Discourage search engines from indexing this site')
                            ->helperText('It is up to search engines to honor this request.')
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return ReadingSettings::class;
    }

    public function getOrder(): int
    {
        return 20;
    }

    public function getTabKey(): string
    {
        return 'reading';
    }
}
