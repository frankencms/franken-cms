<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Post\Schemas;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FilamentTiptapEditor\TiptapEditor;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Enums\PostType;
use FrankenCms\Filament\Forms\Components\TitleWithSlugInput;
use FrankenCms\Models\Post;
use FrankenCms\Settings\CmsSettings;

class PostForm
{
    public static function make(Schema $schema): Schema
    {
        $settings = new CmsSettings;

        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm'      => 1,
                    'md'      => 6,
                    'lg'      => 12,
                ])
                    ->columnSpanFull()
                    ->schema([
                        Grid::make()
                            ->columnSpan([
                                'sm' => 1,
                                'md' => 4,
                                'lg' => 8,
                            ])
                            ->schema([
                                Section::make(__('Post Details'))
                                    ->columnSpanFull()
                                    ->schema([
                                        Hidden::make('post_type')
                                            ->default(PostType::POST->value),

                                        TitleWithSlugInput::make(
                                            fieldTitle: 'post_title',
                                            fieldSlug: 'post_slug',
                                            titleLabel: 'Page Name',
                                            slugLabel: 'Permalink',
                                            urlPath: sprintf('/%s/', $settings->post_page),
                                            slugRules: [
                                                'required',
                                                fn (?Post $record) => 'unique:posts,post_slug,' . ($record?->id ?? 'NULL') . ',id',
                                            ],
                                        ),

                                        RichEditor::make('post_content')
                                            ->json(),

                                        //                                        TiptapEditor::make('post_content')
                                        //                                            ->output(TiptapOutput::Json)
                                        //                                            ->label('Content')
                                        //                                            ->columnSpan('full')
                                        //                                            ->collapseBlocksPanel()
                                        //                                            ->extraInputAttributes(['style' => 'min-height: 24rem;']),
                                    ]),
                            ]),

                        Grid::make()
                            ->columnSpan([
                                'sm' => 1,
                                'md' => 2,
                                'lg' => 4,
                            ])
                            ->schema([
                                Section::make(__('Post Status'))
                                    ->columnSpanFull()
                                    ->schema([
                                        Select::make('post_status')
                                            ->label('Status')
                                            ->inlineLabel(true)
                                            ->selectablePlaceholder(false)
                                            ->options(PostStatus::class)
                                            ->default(PostStatus::DRAFT),

                                        DateTimePicker::make('post_published_at')
                                            ->label('Publish Date')
                                            ->timezone(fn (CmsSettings $settings) => $settings->timezone) // TODO: UNKNOWN TIME ZONE
                                            ->default(now())
                                            ->required(),

                                        Select::make('post_author_id')
                                            ->relationship('author', 'name')
                                            ->searchable()
                                            ->required()
                                            ->default(fn () => auth()->id())
                                            ->label('Author'),
                                    ]),

                                Section::make('Featured Image')
                                    ->description('')
                                    ->columnSpanFull()
                                    ->schema([
                                        // TODO: FIX OR REPLACE
                                        //                                        CuratorPicker::make('featured_image_id')
                                        //                                            ->relationship('featuredImage', 'id'),
                                    ]),

                                Section::make(trans('filament-cms::messages.content.posts.sections.meta.title'))
                                    ->visible(fn ($record) => $record && ! empty($record->meta_url))
                                    ->description(trans('filament-cms::messages.content.posts.sections.meta.description'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
