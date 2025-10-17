<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Concerns;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

                Section::make('Open Graph (Social Media)')
                    ->description('Configure how this content appears when shared on Facebook, LinkedIn, etc.')
                    ->schema([
                        TextInput::make('seo_og_title')
                            ->label('OpenGraph Title')
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
                            ->label('OpenGraph Description')
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
                            ->label('OpenGraph Image')
                            ->helperText('Image for social media shares (1200×630px recommended). Leave blank to use default.')
                            ->collection('seo-og')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null, // Free crop
                                '1.91:1', // 1200x630 (OG recommended)
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Section::make('Twitter Card')
                    ->description('Configure how this content appears on Twitter/X')
                    ->schema([
                        TextInput::make('seo_twitter_title')
                            ->label('Twitter Title')
                            ->helperText('Title for Twitter cards. Leave blank to use OpenGraph title.')
                            ->maxLength(70)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_twitter_title', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_twitter_title', $state);
                                }
                            })
                            ->columnSpanFull(),

                        Textarea::make('seo_twitter_description')
                            ->label('Twitter Description')
                            ->helperText('Description for Twitter cards. Leave blank to use OpenGraph description.')
                            ->rows(3)
                            ->maxLength(200)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_twitter_description', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_twitter_description', $state);
                                }
                            })
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('seo_twitter_image')
                            ->label('Twitter Card Image')
                            ->helperText('Image for Twitter cards (1200×675px recommended). Leave blank to use OpenGraph image.')
                            ->collection('seo-twitter')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null, // Free crop
                                '16:9', // 1200x675 (Twitter recommended)
                                '1.91:1', // 1200x630 (OG)
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120)
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
            'seo_twitter_title',
            'seo_twitter_description',
        ];
    }
}
