<?php

namespace FrankenCms\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Form;
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
                                    TextInput::make('label')
                                        ->label('Label')
                                        ->inlineLabel()
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('url')
                                        ->label('Custom URL')
                                        ->inlineLabel()
                                        ->url()
                                        ->placeholder('https://example.com')
                                        ->helperText('Leave empty to use route or linkable model'),

                                    TextInput::make('route_name')
                                        ->label('Route Name')
                                        ->inlineLabel()
                                        ->placeholder('post.show')
                                        ->helperText('Laravel route name'),

                                    Select::make('linkable_type')
                                        ->label('Link Type')
                                        ->inlineLabel()
                                        ->options([
                                            ''          => 'No Link',
                                            Post::class => 'Post',
                                        ])
                                        ->live(),

                                    Select::make('linkable_id')
                                        ->label('Select Content')
                                        ->inlineLabel()
                                        ->options(function (callable $get) {
                                            $type = $get('linkable_type');
                                            if ($type === Post::class) {
                                                return Post::pluck('post_title', 'id')->toArray();
                                            }
                                            return [];
                                        })
                                        ->searchable()
                                        ->visible(fn (callable $get) => ! empty($get('linkable_type'))),

                                    Select::make('target')
                                        ->label('Link Target')
                                        ->inlineLabel()
                                        ->options(LinkTargets::class)
                                        ->default(LinkTargets::_SELF->value),

                                    TextInput::make('parent_id')
                                        ->label('Parent Item ID')
                                        ->inlineLabel()
                                        ->numeric()
                                        ->helperText('Leave empty for top-level items'),

                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->inlineLabel()
                                        ->default(true),

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
