<?php

namespace FrankenCms\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FrankenCms\Models\Menu;

class MenusForm
{
    public static function make(Schema $schema): Schema
    {

        return $schema
            ->components([

                Section::make('Menu Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $context, $state, callable $set) {
                                if ($context === 'create') {
                                    $set('slug', str($state)->slug()->toString());
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Menu::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Section::make('Additional Data')
                    ->columnSpanFull()
                    ->schema([
                        KeyValue::make('additional_data')
                            ->label('Additional Data')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Store additional metadata for this menu (e.g., CSS classes, display options, etc.)')
                            ->columnSpanFull(),
                    ]),

            ]);

    }
}
