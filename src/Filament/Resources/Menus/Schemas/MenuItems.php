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
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Rules\MenuItemUrl;

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

                                    Select::make('link_to')
                                        ->label('Link To')
                                        ->inlineLabel()
                                        ->options(function () {
                                            // Custom URL option at the top
                                            $options = ['custom' => 'Custom URL'];

                                            // Get all pages (Page model has PageScope that filters by post_type='page')
                                            $pages = Page::query()
                                                ->where('post_status', 'published')
                                                ->orderBy('post_title')
                                                ->get();

                                            $pageOptions = [];
                                            foreach ($pages as $page) {
                                                $pageOptions['page:' . $page->id] = $page->post_title;
                                            }
                                            if (! empty($pageOptions)) {
                                                $options['Pages'] = $pageOptions;
                                            }

                                            // Get all posts (explicitly filter by post_type='post')
                                            $posts = Post::query()
                                                ->where('post_type', 'post')
                                                ->where('post_status', 'published')
                                                ->orderBy('post_title')
                                                ->get();

                                            $postOptions = [];
                                            foreach ($posts as $post) {
                                                $postOptions['post:' . $post->id] = $post->post_title;
                                            }
                                            if (! empty($postOptions)) {
                                                $options['Posts'] = $postOptions;
                                            }

                                            return $options;
                                        })
                                        ->searchable()
                                        ->live()
                                        ->required(),

                                    TextInput::make('url')
                                        ->label('URL')
                                        ->inlineLabel()
                                        ->placeholder('https://example.com or /about')
                                        ->helperText('Accepts full URLs, relative paths (/about), anchors (#section), and mailto:/tel: links. Relative paths are converted to full URLs on output.')
                                        ->visible(fn (callable $get): bool => $get('link_to') === 'custom')
                                        ->required(fn (callable $get): bool => $get('link_to') === 'custom')
                                        ->rule(new MenuItemUrl)
                                        ->dehydrated(),

                                    // Hidden fields to store the polymorphic relationship
                                    TextInput::make('linkable_type')
                                        ->hidden()
                                        ->dehydrated(),

                                    TextInput::make('linkable_id')
                                        ->hidden()
                                        ->dehydrated(),

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
