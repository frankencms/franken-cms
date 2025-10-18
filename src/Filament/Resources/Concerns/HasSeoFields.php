<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Concerns;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use FrankenCms\Settings\SeoSettings;

trait HasSeoFields
{
    /**
     * Get the SEO tab for Filament forms
     */
    public static function getSeoTab(): Tab
    {
        return Tab::make('SEO')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Section::make('Meta Tags')
                    ->description('Configure how this page appears in search results')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO Title')
                            ->helperText('The title that appears in search results. Leave blank to use the post/page title.')
                            ->maxLength(60)
                            ->live(debounce: 500)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_title', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, callable $set, $record): void {
                                $set('_seo_title_length', mb_strlen($state ?? ''));
                                if ($record) {
                                    $record->setMeta('seo_title', $state);
                                }
                            })
                            ->columnSpanFull(),

                        Placeholder::make('_seo_title_length')
                            ->label('Title Length')
                            ->content(function (Get $get) {
                                $length = mb_strlen($get('seo_title') ?? '');
                                $color = $length <= 60 ? 'success' : 'danger';

                                return new \Illuminate\Support\HtmlString(
                                    "<span style='color: var(--{$color}-600);'>{$length} / 60 characters</span>"
                                );
                            })
                            ->columnSpanFull(),

                        Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->helperText('A brief description that appears in search results. 150-160 characters recommended.')
                            ->rows(3)
                            ->maxLength(160)
                            ->live(debounce: 500)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_description', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, callable $set, $record): void {
                                $set('_seo_description_length', mb_strlen($state ?? ''));
                                if ($record) {
                                    $record->setMeta('seo_description', $state);
                                }
                            })
                            ->columnSpanFull(),

                        Placeholder::make('_seo_description_length')
                            ->label('Description Length')
                            ->content(function (Get $get) {
                                $length = mb_strlen($get('seo_description') ?? '');
                                $color = $length >= 150 && $length <= 160 ? 'success' : ($length > 160 ? 'danger' : 'warning');

                                return new \Illuminate\Support\HtmlString(
                                    "<span style='color: var(--{$color}-600);'>{$length} / 160 characters</span>"
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Advanced SEO')
                    ->description('Fine-tune how search engines handle this page')
                    ->schema([
                        TextInput::make('seo_canonical_url')
                            ->label('Canonical URL')
                            ->helperText('Override the canonical URL. Leave blank to use the default URL.')
                            ->url()
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_canonical_url', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_canonical_url', $state);
                                }
                            })
                            ->columnSpanFull(),

                        Select::make('seo_robots_index')
                            ->label('Index Setting')
                            ->helperText('Control whether search engines should index this page')
                            ->options([
                                '' => 'Use Default (' . (app(SeoSettings::class)->default_robots_index ?? 'index') . ')',
                                'index' => 'Index',
                                'noindex' => 'No Index',
                            ])
                            ->default('')
                            ->native(false)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_robots_index', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_robots_index', $state);
                                }
                            })
                            ->columnSpan(1),

                        Select::make('seo_robots_follow')
                            ->label('Follow Setting')
                            ->helperText('Control whether search engines should follow links on this page')
                            ->options([
                                '' => 'Use Default (' . (app(SeoSettings::class)->default_robots_follow ?? 'follow') . ')',
                                'follow' => 'Follow',
                                'nofollow' => 'No Follow',
                            ])
                            ->default('')
                            ->native(false)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_robots_follow', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_robots_follow', $state);
                                }
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('Social Media Sharing')
                    ->description('Configure how this content appears when shared on social media (Facebook, Twitter, LinkedIn, etc.)')
                    ->schema([
                        TextInput::make('seo_og_title')
                            ->label('Social Media Title')
                            ->helperText('Title for social media shares. Leave blank to use SEO title or post/page title.')
                            ->maxLength(95)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_og_title', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_og_title', $state);
                                }
                            })
                            ->columnSpanFull(),

                        Textarea::make('seo_og_description')
                            ->label('Social Media Description')
                            ->helperText('Description for social media shares. Leave blank to use meta description.')
                            ->rows(3)
                            ->maxLength(200)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_og_description', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_og_description', $state);
                                }
                            })
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('seo_og_image')
                            ->label('Social Media Image')
                            ->helperText('Image for social media shares (1200×630px recommended). Used for Facebook, LinkedIn, Twitter, and other platforms. Leave blank to use default or featured image.')
                            ->collection('seo-og')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1.91:1', // 1200x630 (OG recommended)
                                null, // Free crop
                            ])
                            ->maxSize(5120)
                            ->columnSpanFull(),

                        Toggle::make('seo_use_twitter_summary')
                            ->label('Use Twitter Summary Card (Small Square Image)')
                            ->helperText('Enable to use a small square Twitter card instead of the large image card above')
                            ->default(fn () => app(\FrankenCms\Settings\SeoSettings::class)->use_twitter_summary_card)
                            ->live()
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    // Load from postmeta if exists, otherwise use global setting
                                    $globalSetting = app(\FrankenCms\Settings\SeoSettings::class)->use_twitter_summary_card;
                                    $component->state((bool) $record->getMeta('seo_use_twitter_summary', $globalSetting));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_use_twitter_summary', (bool) $state);
                                }
                            })
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('seo_twitter_image')
                            ->label('Twitter Summary Card Image')
                            ->helperText('Square image for Twitter summary cards (minimum 240×240px, recommended 600×600px). Only used when summary card is enabled above.')
                            ->collection('seo-twitter')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1', // Square for summary cards
                                null, // Free crop
                            ])
                            ->maxSize(5120)
                            ->visible(fn (Get $get) => $get('seo_use_twitter_summary'))
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    /**
     * Get the list of SEO meta fields that should be stored in postmeta
     */
    public static function getSeoMetaFields(): array
    {
        return [
            'seo_title',
            'seo_description',
            'seo_canonical_url',
            'seo_robots_index',
            'seo_robots_follow',
            'seo_og_title',
            'seo_og_description',
            'seo_use_twitter_summary',
        ];
    }
}
