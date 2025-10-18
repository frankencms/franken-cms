<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Settings\MediaSettings;

class MediaSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Media')
            ->icon('heroicon-o-photo')
            ->schema([

                Section::make('Post Image Conversions')
                    ->description('Configure how featured images are displayed on single posts and in post listings. Images will be automatically resized and cropped based on these settings.')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Featured Image (Single Post View)')
                            ->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('featured_aspect_ratio')
                                    ->label('Aspect Ratio')
                                    ->inlineLabel()
                                    ->options([
                                        '16:9' => '16:9 (Widescreen)',
                                        '4:3' => '4:3 (Traditional)',
                                        '3:2' => '3:2 (Classic Photo)',
                                        '1:1' => '1:1 (Square)',
                                        '21:9' => '21:9 (Ultrawide)',
                                        'custom' => 'Custom Dimensions',
                                    ])
                                    ->default('16:9')
                                    ->required()
                                    ->live()
                                    ->helperText('The aspect ratio for featured images on single post pages.')
                                    ->columnSpan(2),

                                TextInput::make('featured_width')
                                    ->label('Max Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(1200)
                                    ->required()
                                    ->helperText('Maximum width in pixels. Height will be calculated from aspect ratio.')
                                    ->visible(fn ($get) => $get('featured_aspect_ratio') !== 'custom')
                                    ->columnSpan(2),

                                TextInput::make('featured_custom_width')
                                    ->label('Custom Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->required()
                                    ->visible(fn ($get) => $get('featured_aspect_ratio') === 'custom')
                                    ->columnSpan(2),

                                TextInput::make('featured_custom_height')
                                    ->label('Custom Height')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->required()
                                    ->visible(fn ($get) => $get('featured_aspect_ratio') === 'custom')
                                    ->columnSpan(2),

                                Toggle::make('featured_crop')
                                    ->label('Crop To Exact Dimensions')
                                    ->inlineLabel()
                                    ->helperText('Crop images to exact dimensions. If disabled, images will be resized proportionally within the dimensions.')
                                    ->default(true)
                                    ->columnSpan(2),
                            ]),

                        Fieldset::make('Listing Image (Blog Index & Archive Pages)')
                            ->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('listing_aspect_ratio')
                                    ->label('Aspect Ratio')
                                    ->inlineLabel()
                                    ->options([
                                        '16:9' => '16:9 (Widescreen)',
                                        '4:3' => '4:3 (Traditional)',
                                        '3:2' => '3:2 (Classic Photo)',
                                        '1:1' => '1:1 (Square)',
                                        '21:9' => '21:9 (Ultrawide)',
                                        'custom' => 'Custom Dimensions',
                                    ])
                                    ->default('3:2')
                                    ->required()
                                    ->live()
                                    ->helperText('The aspect ratio for images in post listings and archives.')
                                    ->columnSpan(2),

                                TextInput::make('listing_width')
                                    ->label('Max Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(800)
                                    ->required()
                                    ->helperText('Maximum width in pixels. Height will be calculated from aspect ratio.')
                                    ->visible(fn ($get) => $get('listing_aspect_ratio') !== 'custom')
                                    ->columnSpan(2),

                                TextInput::make('listing_custom_width')
                                    ->label('Custom Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->required()
                                    ->visible(fn ($get) => $get('listing_aspect_ratio') === 'custom')
                                    ->columnSpan(2),

                                TextInput::make('listing_custom_height')
                                    ->label('Custom Height')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->required()
                                    ->visible(fn ($get) => $get('listing_aspect_ratio') === 'custom')
                                    ->columnSpan(2),

                                Toggle::make('listing_crop')
                                    ->label('Crop To Exact Dimensions')
                                    ->inlineLabel()
                                    ->helperText('Crop images to exact dimensions. If disabled, images will be resized proportionally within the dimensions.')
                                    ->default(true)
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Responsive Images')
                    ->description('Configure responsive image generation for better performance and bandwidth savings.')
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('enable_responsive_images')
                            ->label('Enable Responsive Images')
                            ->helperText('Generate multiple image sizes (srcset) for responsive delivery. Automatically serves optimized image sizes based on device and screen resolution.')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    public function getSettingsClass(): string
    {
        return MediaSettings::class;
    }

    public function getOrder(): int
    {
        return 50;
    }

    public function getTabKey(): string
    {
        return 'media';
    }
}
