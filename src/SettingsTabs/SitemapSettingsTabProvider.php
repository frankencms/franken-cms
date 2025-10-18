<?php

declare(strict_types=1);

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Settings\SitemapSettings;

class SitemapSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTabKey(): string
    {
        return 'sitemap';
    }

    public function getTab(): Tab
    {
        return Tab::make('Sitemap')
            ->icon('heroicon-o-map')
            ->schema([
                Section::make('XML Sitemap Configuration')
                    ->description('Configure automatic XML sitemap generation for search engines.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable Sitemap Generation')
                            ->helperText('Automatically generate XML sitemaps for your content.')
                            ->default(true)
                            ->columnSpanFull(),

                        CheckboxList::make('included_post_types')
                            ->label('Include Content Types')
                            ->helperText('Select which post types to include in the sitemap')
                            ->options([
                                'post' => 'Posts',
                                'page' => 'Pages',
                            ])
                            ->default(['post', 'page'])
                            ->required()
                            ->columnSpanFull()
                            ->columns(2),

                        Select::make('default_change_frequency')
                            ->label('Default Change Frequency')
                            ->helperText('How frequently content typically changes (hint for search engines)')
                            ->options([
                                'always' => 'Always',
                                'hourly' => 'Hourly',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'never' => 'Never',
                            ])
                            ->default('weekly')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('default_priority')
                            ->label('Default Priority')
                            ->helperText('Priority of URLs relative to other pages (0.0 to 1.0, where 1.0 is highest)')
                            ->numeric()
                            ->minValue(0.0)
                            ->maxValue(1.0)
                            ->step(0.1)
                            ->default(0.5)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('max_urls_per_sitemap')
                            ->label('Maximum URLs per Sitemap')
                            ->helperText('Google recommends 50,000 URLs max. Larger sites will use sitemap index.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50000)
                            ->default(50000)
                            ->required()
                            ->columnSpanFull(),

                        TagsInput::make('excluded_paths')
                            ->label('Excluded Paths')
                            ->helperText('Enter paths to exclude from sitemap (e.g., "/private", "/admin/*")')
                            ->placeholder('/private')
                            ->columnSpanFull(),

                        TagsInput::make('custom_sitemaps')
                            ->label('Custom Sitemaps')
                            ->helperText('Additional sitemap URLs to include in the sitemap index (e.g., "/news-sitemap.xml")')
                            ->placeholder('/custom-sitemap.xml')
                            ->columnSpanFull(),

                        Toggle::make('include_images')
                            ->label('Include Featured Images')
                            ->helperText('Add featured images to sitemap entries for better image SEO')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return SitemapSettings::class;
    }

    public function getOrder(): int
    {
        return 70; // After Robots (60)
    }
}
