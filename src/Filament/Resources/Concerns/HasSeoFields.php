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
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_title', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_title', $state);
                                }
                            })
                            ->extraInputAttributes([
                                'x-init' => "\$dispatch('seo-title-update', { length: \$el.value.length })",
                                'x-on:input.debounce.50ms' => "\$dispatch('seo-title-update', { length: \$el.value.length })",
                            ])
                            ->columnSpanFull(),

                        Placeholder::make('_seo_title_length')
                            ->label('Title Length')
                            ->content(function () {
                                $seoSettings = app(\FrankenCms\Settings\SeoSettings::class);

                                // Calculate additional characters from site name if appending is enabled
                                $additionalLength = 0;
                                if ($seoSettings->append_site_name) {
                                    $siteName = $seoSettings->site_name ?? '';
                                    $separator = $seoSettings->title_separator ?? '-';
                                    // Account for separator with spaces: " - "
                                    $additionalLength = mb_strlen($siteName) + mb_strlen($separator) + 2;
                                }

                                return new \Illuminate\Support\HtmlString(
                                    "<div
                                        wire:ignore
                                        x-data='{
                                            titleLength: 0,
                                            additionalLength: {$additionalLength}
                                        }'
                                        x-on:seo-title-update.window='titleLength = \$event.detail.length'
                                    >
                                        <span
                                            x-text='
                                                (() => {
                                                    const total = titleLength + additionalLength;
                                                    if (titleLength === 0) return \"Using default title\";

                                                    let msg = titleLength + \" characters\";
                                                    if (additionalLength > 0) {
                                                        msg += \" (+\" + additionalLength + \" from site name) = \" + total + \" total\";
                                                    }
                                                    msg += \" / 60 recommended\";

                                                    if (total > 60) {
                                                        // Too long
                                                    } else if (total < 50 && titleLength > 0) {
                                                        msg += \" (too short)\";
                                                    }

                                                    return msg;
                                                })()
                                            '
                                            x-bind:style='
                                                (() => {
                                                    const total = titleLength + additionalLength;
                                                    let color = \"gray\";

                                                    if (titleLength === 0) color = \"gray\";
                                                    else if (total > 60) color = \"danger\";
                                                    else if (total >= 50 && total <= 60) color = \"success\";
                                                    else if (total > 0 && total < 50) color = \"warning\";

                                                    return \"color: var(--\" + color + \"-600);\";
                                                })()
                                            '
                                        ></span>
                                    </div>"
                                );
                            })
                            ->columnSpanFull(),

                        Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->helperText('A brief description that appears in search results. 150-160 characters recommended.')
                            ->rows(3)
                            ->maxLength(160)
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                if ($record) {
                                    $component->state($record->getMeta('seo_description', ''));
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, $record): void {
                                if ($record) {
                                    $record->setMeta('seo_description', $state);
                                }
                            })
                            ->extraInputAttributes([
                                'x-init' => "\$dispatch('seo-description-update', { length: \$el.value.length })",
                                'x-on:input.debounce.50ms' => "\$dispatch('seo-description-update', { length: \$el.value.length })",
                            ])
                            ->columnSpanFull(),

                        Placeholder::make('_seo_description_length')
                            ->label('Description Length')
                            ->content(function () {
                                return new \Illuminate\Support\HtmlString(
                                    "<div
                                        wire:ignore
                                        x-data='{ descLength: 0 }'
                                        x-on:seo-description-update.window='descLength = \$event.detail.length'
                                    >
                                        <span
                                            x-text='
                                                (() => {
                                                    if (descLength === 0) return \"Using default description\";

                                                    let msg = descLength + \" / 160 characters\";

                                                    if (descLength > 160) {
                                                        msg += \" (too long)\";
                                                    } else if (descLength >= 120 && descLength <= 160) {
                                                        msg += \" (good)\";
                                                    } else if (descLength > 0 && descLength < 120) {
                                                        msg += \" (too short - aim for 120-160)\";
                                                    }

                                                    return msg;
                                                })()
                                            '
                                            x-bind:style='
                                                (() => {
                                                    let color = \"gray\";

                                                    if (descLength === 0) color = \"gray\";
                                                    else if (descLength > 160) color = \"danger\";
                                                    else if (descLength >= 120 && descLength <= 160) color = \"success\";
                                                    else if (descLength > 0 && descLength < 120) color = \"warning\";

                                                    return \"color: var(--\" + color + \"-600);\";
                                                })()
                                            '
                                        ></span>
                                    </div>"
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
