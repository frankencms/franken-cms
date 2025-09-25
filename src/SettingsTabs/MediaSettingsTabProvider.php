<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
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
            ->schema([
                Section::make('Image Sizes')
                    ->description('The sizes listed below determine the maximum dimensions in pixels to use when adding an image to the Media Library.')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Thumbnail')
                            ->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('thumbnail_width')
                                    ->label('Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(150)
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('thumbnail_height')
                                    ->label('Height')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(150)
                                    ->required()
                                    ->columnSpan(2),
                                Checkbox::make('thumbnail_crop')
                                    ->inlineLabel()
                                    ->label('Crop Thumbnail To Exact Dimensions')
                                    ->helperText('Normally thumbnails are proportional to the original image. Enable this to crop the thumbnail to exact dimensions.')
                                    ->columnSpan(2),
                            ]),

                        Fieldset::make('Medium Size')
                            ->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('medium_width')
                                    ->label('Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(300)
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('medium_height')
                                    ->label('Height')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(300)
                                    ->required()
                                    ->columnSpan(2),
                            ]),

                        Fieldset::make('Large Size')
                            ->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('large_width')
                                    ->label('Width')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(1024)
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('large_height')
                                    ->label('Height')
                                    ->inlineLabel()
                                    ->postfix('px')
                                    ->numeric()
                                    ->default(1024)
                                    ->required()
                                    ->columnSpan(2),
                            ]),
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
