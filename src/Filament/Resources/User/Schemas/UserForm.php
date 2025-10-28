<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\User\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->inlineLabel()
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Author Biography')
                    ->description('This information will be displayed on blog posts authored by this user.')
                    ->relationship('bio')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('bio_image')
                            ->label('Profile Image')
                            ->inlineLabel()
                            ->collection('bio-image')
                            ->disk(config('franken-cms.media_disk_name', 'public'))
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1', null])
                            ->previewable()
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->visibility('public')
                            ->multiple(false)
                            ->helperText('Square images work best. Recommended size: 400x400px'),

                        TextInput::make('title')
                            ->label('Job Title')
                            ->inlineLabel()
                            ->placeholder('e.g., Senior Developer, Content Writer')
                            ->maxLength(255),
                        RichEditor::make('bio')
                            ->label('Biography')
                            ->inlineLabel()
                            ->placeholder('Tell us about yourself...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                                'undo',
                                'redo',
                            ])
                            ->maxLength(65535),
                        TextInput::make('website')
                            ->label('Website')
                            ->inlineLabel()
                            ->url()
                            ->placeholder('https://example.com')
                            ->maxLength(255),
                        KeyValue::make('social_links')
                            ->label('Social Media Links')
                            ->inlineLabel()
                            ->keyLabel('Platform')
                            ->valueLabel('URL')
                            ->addActionLabel('Add social link')
                            ->reorderable(),
                    ])
                    ->collapsible(),
            ]);
    }
}
