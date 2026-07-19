<?php

declare(strict_types=1);

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\OgImage\OgImageFeature;
use FrankenCms\Settings\SeoSettings;

class SeoSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        $group = SeoSettings::group();

        return Tab::make('SEO')
            ->icon('heroicon-o-magnifying-glass')
            ->statePath($group)
            ->schema([
                Section::make('General SEO Settings')
                    ->description('Configure your site\'s basic SEO information')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->helperText('The name of your website (used in meta tags and schemas)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('site_tagline')
                            ->label('Site Tagline')
                            ->helperText('A brief description or slogan for your site')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('default_meta_description')
                            ->label('Default Meta Description')
                            ->helperText('Fallback description when pages don\'t have their own')
                            ->rows(3)
                            ->maxLength(160)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Page Title Format')
                    ->collapsible()
                    ->collapsed()
                    ->description('Control how page titles appear in search results and browser tabs')
                    ->schema([
                        Toggle::make('append_site_name')
                            ->label('Append Site Name to Page Titles')
                            ->helperText('When enabled, your site name will be added to page titles (e.g., "Page Title - Site Name")')
                            ->default(true)
                            ->live()
                            ->inline(false)
                            ->columnSpanFull(),

                        Radio::make('site_name_position')
                            ->label('Site Name Position')
                            ->helperText('Choose whether the site name appears before or after the page title')
                            ->options([
                                'append'  => 'After page title (Page Title - Site Name)',
                                'prepend' => 'Before page title (Site Name - Page Title)',
                            ])
                            ->default('append')
                            ->required()
                            ->visible(fn (Get $get) => $get('append_site_name') === true)
                            ->columnSpanFull(),

                        Select::make('title_separator')
                            ->label('Title Separator')
                            ->helperText('Character used to separate page title from site name')
                            ->options([
                                '-'  => '- (Dash)',
                                '|'  => '| (Pipe)',
                                '–'  => '– (En Dash)',
                                '—'  => '— (Em Dash)',
                                '·'  => '· (Middle Dot)',
                                '•'  => '• (Bullet)',
                                '/'  => '/ (Slash)',
                                '\\' => '\\ (Backslash)',
                                '>'  => '> (Greater Than)',
                                '<'  => '< (Less Than)',
                            ])
                            ->default('-')
                            ->required()
                            ->native(false)
                            ->visible(fn (Get $get) => $get('append_site_name') === true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Canonical URLs & Robots')
                    ->collapsible()
                    ->collapsed()
                    ->description('Control how search engines index your content')
                    ->schema([
                        Toggle::make('enable_canonical')
                            ->label('Enable Canonical URLs')
                            ->helperText('Automatically add canonical URL tags to prevent duplicate content issues')
                            ->default(true)
                            ->inline(false),

                        Select::make('default_robots_index')
                            ->label('Default Index Behavior')
                            ->helperText('Tell search engines whether to index your pages by default')
                            ->options([
                                'index'   => 'Index (Allow search engines to index)',
                                'noindex' => 'No Index (Prevent search engines from indexing)',
                            ])
                            ->default('index')
                            ->required()
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->columnSpan(1),

                        Select::make('default_robots_follow')
                            ->label('Default Follow Behavior')
                            ->helperText('Tell search engines whether to follow links on your pages')
                            ->options([
                                'follow'   => 'Follow (Allow search engines to follow links)',
                                'nofollow' => 'No Follow (Prevent search engines from following links)',
                            ])
                            ->default('follow')
                            ->required()
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Social Media Sharing')
                    ->collapsible()
                    ->collapsed()
                    ->description('Configure how your content appears when shared on social media platforms (Facebook, Twitter, LinkedIn, etc.)')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('og_default_image')
                            ->label('Default Social Media Image')
                            ->helperText(function () {
                                $base = 'Default image for social sharing (1200×630px recommended). Used for Facebook, LinkedIn, Twitter, and other platforms when individual pages don\'t have their own image.';

                                if (! OgImageFeature::isEnabled()) {
                                    return $base;
                                }

                                if (OgImageFeature::defaultTemplate()) {
                                    return $base . ' OG image generation is active: content types with a template get a generated image instead, and if you leave this empty the site-wide fallback template generates one automatically.';
                                }

                                return $base . ' OG image generation is active: content types with a template in config/franken-cms.php get a generated image instead of this one.';
                            })
                            ->collection('og-default')
                            ->model(fn () => SiteSettingsMedia::getInstance())
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1.91:1', // 1200x630 (OG recommended)
                                null, // Free crop
                            ])
                            ->maxSize(5120)
                            ->columnSpanFull(),

                        Select::make('og_type')
                            ->label('OpenGraph Type')
                            ->helperText('The type of content your site represents')
                            ->options([
                                'website' => 'Website',
                                'blog'    => 'Blog',
                                'article' => 'Article',
                            ])
                            ->default('website')
                            ->required()
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->columnSpan(1),

                        TextInput::make('fb_app_id')
                            ->label('Facebook App ID')
                            ->helperText('Your Facebook App ID for Facebook Insights (optional)')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('twitter_username')
                            ->label('Twitter/X Username')
                            ->helperText('Your Twitter/X handle (e.g., @yourhandle)')
                            ->prefix('@')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Toggle::make('use_twitter_summary_card')
                            ->label('Use Twitter Summary Card')
                            ->helperText('Enable to use small square Twitter cards instead of large image cards')
                            ->default(false)
                            ->live()
                            ->columnSpan(1),

                        SpatieMediaLibraryFileUpload::make('twitter_default_image')
                            ->label('Twitter Summary Card Image')
                            ->helperText('Square image for Twitter summary cards (minimum 240×240px, recommended 600×600px). Only used when summary card is enabled above.')
                            ->collection('twitter-default')
                            ->model(fn () => SiteSettingsMedia::getInstance())
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1', // Square for summary cards
                                null, // Free crop
                            ])
                            ->maxSize(5120)
                            ->required(fn (Get $get) => $get('use_twitter_summary_card'))
                            ->visible(fn (Get $get) => $get('use_twitter_summary_card'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Additional Settings')
                    ->collapsible()
                    ->collapsed()
                    ->description('Theme color and other appearance settings')
                    ->schema([
                        TextInput::make('theme_color')
                            ->label('Theme Color')
                            ->helperText('Browser theme color for mobile devices (hex color code)')
                            ->prefix('#')
                            ->maxLength(7)
                            ->default('000000')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return SeoSettings::class;
    }

    public function getOrder(): int
    {
        return 30;
    }

    public function getTabKey(): string
    {
        return 'seo';
    }
}
