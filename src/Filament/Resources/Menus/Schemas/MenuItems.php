<?php

namespace FrankenCms\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FrankenCms\Enums\LinkTargets;
use FrankenCms\Models\Post;

class MenuItems
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Menu Items')
                        ->description('Drag and drop to reorder menu items. Nested items will appear as submenus.')
                        ->schema([
                            Repeater::make('menu_items')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('label')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(1),

                                            Select::make('target')
                                                ->options(LinkTargets::class)
                                                ->default(LinkTargets::_SELF->value)
                                                ->columnSpan(1),

                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true)
                                                ->columnSpan(1),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('url')
                                                ->label('Custom URL')
                                                ->url()
                                                ->placeholder('https://example.com')
                                                ->helperText('Leave empty to use route or linkable model')
                                                ->columnSpan(1),

                                            TextInput::make('route_name')
                                                ->label('Route Name')
                                                ->placeholder('post.show')
                                                ->helperText('Laravel route name')
                                                ->columnSpan(1),
                                        ]),

                                    Select::make('linkable_type')
                                        ->label('Link Type')
                                        ->options([
                                            ''          => 'No Link',
                                            Post::class => 'Post',
                                        ])
                                        ->live()
                                        ->columnSpan(1),

                                    Select::make('linkable_id')
                                        ->label('Select Content')
                                        ->options(function (callable $get) {
                                            $type = $get('linkable_type');
                                            if ($type === Post::class) {
                                                return Post::pluck('post_title', 'id')->toArray();
                                            }
                                            return [];
                                        })
                                        ->searchable()
                                        ->visible(fn (callable $get) => ! empty($get('linkable_type')))
                                        ->columnSpan(1),

                                    TextInput::make('parent_id')
                                        ->label('Parent Item ID')
                                        ->numeric()
                                        ->helperText('Leave empty for top-level items')
                                        ->columnSpan(1),

                                    KeyValue::make('additional_data')
                                        ->label('Additional Data')
                                        ->keyLabel('Key')
                                        ->valueLabel('Value')
                                        ->helperText('Advanced: Add custom data for this menu item')
                                        ->columnSpanFull(),
                                ])
                                ->defaultItems(0)
                                ->reorderable()
                                ->collapsible()
                                ->cloneable()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->livewireSubmitHandler('save'),
            ]);
    }
}
