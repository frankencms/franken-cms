<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\User\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Author Biography')
                    ->description('This information will be displayed on blog posts authored by this user.')
                    ->schema([
                        TextInput::make('bio.title')
                            ->label('Job Title')
                            ->placeholder('e.g., Senior Developer, Content Writer')
                            ->maxLength(255),
                        Textarea::make('bio.bio')
                            ->label('Biography')
                            ->placeholder('Tell us about yourself...')
                            ->rows(4)
                            ->maxLength(65535),
                        TextInput::make('bio.website')
                            ->label('Website')
                            ->url()
                            ->placeholder('https://example.com')
                            ->maxLength(255),
                        KeyValue::make('bio.social_links')
                            ->label('Social Media Links')
                            ->keyLabel('Platform')
                            ->valueLabel('URL')
                            ->addActionLabel('Add social link')
                            ->reorderable(),
                    ])
                    ->collapsible(),
            ]);
    }
}
