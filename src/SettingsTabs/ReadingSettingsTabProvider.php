<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Models\Page;
use FrankenCms\Settings\ReadingSettings;

class ReadingSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Reading')
            ->icon('heroicon-o-book-open')
            ->columns(3)
            ->schema([
                Section::make(__('franken-cms::messages.settings.reading.title'))
                    ->description(__('franken-cms::messages.settings.reading.description'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('home_page')
                            ->label('Homepage')
                            ->inlineLabel()
                            ->options(
                                Page::query()->pluck('post_title', 'post_slug')
                            )
                            ->searchable()
                            ->nullable()
                            ->helperText('Select which page should be your homepage. If none is selected, the theme\'s welcome page will be displayed.')
                            ->columnSpan(2),

                        Select::make('post_page')
                            ->label('Posts Page')
                            ->inlineLabel()
                            ->options(
                                Page::query()->pluck('post_title', 'post_slug')->toArray()
                            )
                            ->searchable()
                            ->nullable()
                            ->helperText('Select which page should display your blog posts listing.')
                            ->columnSpan(2),

                        TextInput::make('posts_per_page')
                            ->label('Blog Pages Show At Most')
                            ->postfix('posts')
                            ->inlineLabel()
                            ->default(10)
                            ->required()
                            ->columnSpan(2),

                        Toggle::make('enable_feeds')
                            ->label('Enable RSS & Atom Feeds')
                            ->inlineLabel()
                            ->helperText('Allow visitors to subscribe to your content via RSS and Atom feeds. Feeds are accessible at /feed (RSS) and /feed/atom (Atom).')
                            ->default(true)
                            ->live()
                            ->columnSpan(2),

                        TextInput::make('syndicate_feeds')
                            ->label('Syndicate Feeds Show The Most Recent')
                            ->postfix('items')
                            ->inlineLabel()
                            ->helperText('Maximum number of posts to include in your RSS and Atom feeds.')
                            ->default(10)
                            ->required()
                            ->visible(fn ($get) => $get('enable_feeds'))
                            ->columnSpan(2),

                        Radio::make('include_in_feed')
                            ->visible(fn ($get) => $get('enable_feeds'))
                            ->inlineLabel()
                            ->label('For Each Article In A Feed, Include')
                            ->helperText('Full Text: Includes complete article HTML in feed (larger feeds, better for reading in feed readers). Summary: Includes only excerpt text (smaller feeds, encourages click-through to site).')
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