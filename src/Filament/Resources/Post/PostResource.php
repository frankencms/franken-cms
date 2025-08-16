<?php

namespace FrankenCms\Filament\Resources\Post;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FilamentTiptapEditor\TiptapEditor;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Enums\PostType;
use FrankenCms\Forms\Components\TitleWithSlugInput;
use FrankenCms\Models\Post;
use FrankenCms\Settings\CmsSettings;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Form $form): Form
    {

        $settings = new CmsSettings;

        return $form
            ->schema([

                Grid::make([
                    'default' => 1,
                    'sm'      => 1,
                    'md'      => 6,
                    'lg'      => 12,
                ])
                    ->schema([

                        Grid::make()
                            ->columnSpan([
                                'sm' => 1,
                                'md' => 4,
                                'lg' => 8,
                            ])
                            ->schema(
                                [
                                    Section::make(__('Post Details'))

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

                                            TiptapEditor::make('post_content')
                                                ->output(TiptapOutput::Json)
                                                ->label('Content')
                                                ->columnSpan('full')
                                                ->collapseBlocksPanel()
                                                ->extraInputAttributes(['style' => 'min-height: 24rem;']),

                                        ]),

                                    //                                    Section::make(trans('filament-cms::messages.content.posts.sections.seo.title'))
                                    //                                        ->description(trans('filament-cms::messages.content.posts.sections.seo.description'))
                                    //                                        ->schema([
                                    //                                            TextInput::make('short_description')->label(trans('filament-cms::messages.content.posts.sections.seo.columns.short_description')),
                                    //                                            Textarea::make('keywords')->autosize()->label(trans('filament-cms::messages.content.posts.sections.seo.columns.keywords')),
                                    //                                        ]),
                                ]
                            ),

                        Grid::make()
                            ->columnSpan([
                                'sm' => 1,
                                'md' => 2,
                                'lg' => 4,
                            ])
                            ->schema([

                                Section::make(__('Post Status'))
                                    ->schema([

                                        Select::make('post_status')
                                            ->label('Status')
                                            ->inlineLabel(true)
                                            ->selectablePlaceholder(false)

                                            ->options(PostStatus::class)
                                            ->default(PostStatus::DRAFT),

                                        DateTimePicker::make('post_published_at')
                                            ->label('Publish Date')
                                            ->timezone(fn (CmsSettings $settings) => $settings->timezone)
                                            ->default(now())
                                            ->required(),

                                        Select::make('post_author_id')
                                            ->relationship('author', 'name')
                                            // 'author' matches the method name in the Post model,
                                            // 'name' is the attribute from User model to show in the dropdown
                                            ->searchable()
                                            ->required()
                                            ->default(fn () => auth()->id())
                                            ->label('Author'),

                                    ]),

                                Section::make('Featured Image')
                                    ->description('')
                                    ->schema([
                                        CuratorPicker::make('featured_image_id')
                                            ->relationship('featuredImage', 'id'),
                                    ]),
                                //                                Section::make('Author')
                                //                                    ->description('')
                                //                                    ->schema([
                                //                                        //                                        Select::make('author_type')
                                //                                        //                                            ->label(trans('filament-cms::messages.content.posts.sections.author.columns.author_type'))
                                //                                        //                                            ->options(count(FilamentCMSAuthors::getOptions()) ? FilamentCMSAuthors::getOptions()->pluck('name', 'model')->toArray() : [User::class => 'Users'])
                                //                                        //                                            ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set)=> $set('author_id', null))
                                //                                        //                                            ->preload()
                                //                                        //                                            ->live()
                                //                                        //                                            ->searchable(),
                                //                                        //                                        Select::make('author_id')
                                //                                        //                                            ->label(trans('filament-cms::messages.content.posts.sections.author.columns.author'))
                                //                                        //                                            ->options(fn(Forms\Get $get)=> $get('author_type') ? $get('author_type')::pluck('name', 'id')->toArray() : [])
                                //                                        //                                            ->searchable(),
                                //                                    ]),
                                Section::make(trans('filament-cms::messages.content.posts.sections.meta.title'))
                                    ->visible(fn ($record) => $record && ! empty($record->meta_url))
                                    ->description(trans('filament-cms::messages.content.posts.sections.meta.description')),
                                //                                    ->schema([
                                //                                        TextInput::make('github_starts')
                                //                                            ->disabled()
                                //                                            ->numeric()
                                //                                            ->label(trans('filament-cms::messages.content.posts.sections.meta.columns.github_starts')),
                                //                                        TextInput::make('github_watchers')
                                //                                            ->disabled()
                                //                                            ->numeric()
                                //                                            ->label(trans('filament-cms::messages.content.posts.sections.meta.columns.github_watchers')),
                                //                                        TextInput::make('github_forks')
                                //                                            ->disabled()
                                //                                            ->numeric()
                                //                                            ->label(trans('filament-cms::messages.content.posts.sections.meta.columns.github_forks')),
                                //                                        TextInput::make('downloads_total')
                                //                                            ->disabled()
                                //                                            ->numeric()
                                //                                            ->label(trans('filament-cms::messages.content.posts.sections.meta.columns.downloads_total')),
                                //                                        TextInput::make('downloads_monthly')
                                //                                            ->disabled()
                                //                                            ->numeric()
                                //                                            ->label(trans('filament-cms::messages.content.posts.sections.meta.columns.downloads_monthly')),
                                //                                        TextInput::make('downloads_daily')
                                //                                            ->disabled()
                                //                                            ->numeric()
                                //                                            ->label(trans('filament-cms::messages.content.posts.sections.meta.columns.downloads_daily')),
                            ]),
                    ]),

                //                -----------------------

                //
                //                Section::make('SEO Settings')
                //                    ->schema([
                //                        // This is how we embed a HasOne relationship form
                //
                //                        // We'll reference "seoMeta" fields using dot-notation: "seoMeta.meta_title"
                //                        TextInput::make('meta.meta_title')
                //                            ->label('SEO Title')
                //                            ->maxLength(70),
                //
                //                        Textarea::make('meta.meta_description')
                //                            ->label('SEO Description')
                //                            ->maxLength(160),
                //
                //                        TextInput::make('meta.canonical_url')
                //                            ->label('Canonical URL'),
                //
                //                        TextInput::make('meta.og_title')
                //                            ->label('OG Title'),
                //
                //                        Textarea::make('meta.og_description')
                //                            ->label('OG Description'),
                //
                //                        FileUpload::make('meta.og_image')
                //                            ->label('OG Image')
                //                            ->directory('seo_images'),
                //                    ])
                //
                //                    ->collapsible()
                //                    ->collapsed(true),
                //
                //                Section::make('Categories & Tags')
                //                    ->schema([
                //                        Select::make('categories')
                //                            ->multiple()
                //                            ->relationship('categories', 'name')
                //                            ->label('Categories'),
                //
                //                        Select::make('tags')
                //                            ->multiple()
                //                            ->relationship('tags', 'name')
                //                            ->label('Tags'),
                //                    ])
                //                    ->columns(2),
                //
                //                Section::make('Featured Image')
                //                    ->schema([
                //                        FileUpload::make('featured_image')
                //                            ->label('Featured Image')
                //                            ->directory('featured_images'),
                //                    ]),

            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //                Tables\Columns\ImageColumn::make('featured_image'),
                CuratorColumn::make('featured_image_id')
                    ->label('')
                    ->size(50),
                TextColumn::make('post_title')->sortable()->searchable(),
                TextColumn::make('post_slug')->sortable()->searchable(),
                TextColumn::make('terms.name')->label('Terms')->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
