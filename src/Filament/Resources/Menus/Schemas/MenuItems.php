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
                                        ->afterStateHydrated(function (Select $component, callable $get) {
                                            // Populate from existing linkable data when editing
                                            $linkableType = $get('linkable_type');
                                            $linkableId = $get('linkable_id');
                                            $url = $get('url');

                                            // Check linkable first (page/post links store both linkable and url)
                                            if ($linkableType && $linkableId) {
                                                if (str_contains($linkableType, 'Page')) {
                                                    $component->state('page:' . $linkableId);

                                                    return;
                                                } elseif (str_contains($linkableType, 'Post')) {
                                                    $component->state('post:' . $linkableId);

                                                    return;
                                                }
                                            }

                                            // Fall back to custom URL if no linkable
                                            if ($url) {
                                                $component->state('custom');
                                            }
                                        })
                                        ->afterStateUpdated(function (callable $set, $state) {
                                            if ($state === 'custom') {
                                                // Clear linkable fields so user can enter custom URL
                                                $set('url', null);
                                                $set('linkable_type', null);
                                                $set('linkable_id', null);
                                            } elseif ($state && $state !== 'custom') {
                                                // Parse selection and set linkable relationship
                                                [$type, $id] = explode(':', $state);

                                                if ($type === 'page') {
                                                    $page = Page::find($id);
                                                    $set('linkable_type', Page::class);
                                                    // Show computed URL for reference (actual URL computed from linkable at runtime)
                                                    $set('url', $page?->url);
                                                } else {
                                                    $post = Post::find($id);
                                                    $set('linkable_type', Post::class);
                                                    // Show computed URL for reference (actual URL computed from linkable at runtime)
                                                    $set('url', $post?->url);
                                                }

                                                $set('linkable_id', (int) $id);
                                            }
                                        })
                                        ->dehydrated(false)
                                        ->helperText('Select a page/post or choose Custom URL'),

                                    TextInput::make('url')
                                        ->label('URL')
                                        ->inlineLabel()
                                        ->placeholder('https://example.com')
                                        ->readOnly(fn (callable $get) => $get('link_to') !== 'custom' && $get('link_to') !== null)
                                        ->required(fn (callable $get) => $get('link_to') === 'custom')
                                        ->rules(fn (callable $get) => $get('link_to') === 'custom' ? ['url'] : [])
                                        ->dehydrated()
                                        ->helperText(fn (callable $get) => $get('link_to') === 'custom'
                                            ? 'Enter the full URL including https://'
                                            : 'This URL is automatically set from the selected page/post'),

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
