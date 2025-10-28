<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Models\Page;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Settings\ReadingSettings;

class ReadingSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        $group = ReadingSettings::group();

        return Tab::make('Reading')
            ->icon('heroicon-o-book-open')
            ->statePath($group)
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
                    ]),

                Section::make('Default Featured Image')
                    ->description('Set a default image to use when blog posts don\'t have a featured image. This ensures a consistent look on listing pages.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('default_featured_image')
                            ->label('Default Featured Image')
                            ->helperText('This image will be used as a fallback for blog posts without a featured image. The image will be automatically resized according to your Media Settings.')
                            ->collection('default-featured')
                            ->model(fn () => SiteSettingsMedia::getInstance())
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9', // Widescreen
                                '3:2',  // Common listing
                                '4:3',  // Standard
                                null,   // Free crop
                            ])
                            ->maxSize(5120)
                            ->columnSpanFull(),
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
