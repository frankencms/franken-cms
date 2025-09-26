<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Post\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Enums\PostType;
use FrankenCms\Filament\Forms\Components\TitleWithSlugInput;
use FrankenCms\Filament\Plugins\RichEditor\EnhancedImagePlugin;
use FrankenCms\Filament\Plugins\RichEditor\SourceCodePlugin;
use FrankenCms\Helpers\PostHelper;
use FrankenCms\Models\Post;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\ReadingSettings;

class PostForm
{
    public static function make(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);
        $readingSettings = app(ReadingSettings::class);

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
                                            titleLabel: 'Post Name',
                                            slugLabel: 'Permalink',
                                            urlPath: sprintf('/%s/', $readingSettings->post_page ?? 'posts'),
                                            slugRules: [
                                                'required',
                                                fn (?Post $record) => 'unique:posts,post_slug,' . ($record?->id ?? 'NULL') . ',id',
                                            ],
                                        ),

                                        Group::make()->schema([
                                            Textarea::make('post_teaser')
                                                ->helperText('A short excerpt or teaser for the blog post')
                                                ->rows(3)
                                                ->autosize()
                                                ->afterStateHydrated(function ($component, $state, $record): void {
                                                    if ($record) {
                                                        $component->state($record->getMeta('post_teaser', ''));
                                                    }
                                                })
                                                ->dehydrated(false)
                                                ->afterStateUpdated(function ($state, $record): void {
                                                    if ($record) {
                                                        $record->setMeta('post_teaser', $state);
                                                    }
                                                }),
                                            Actions::make([
                                                Action::make('generate_teaser')
                                                    ->label('Generate Teaser')
                                                    ->icon('heroicon-o-sparkles')
                                                // TODO: Abstract create teaser to action if prism is installed
                                                ,
                                            ]),

                                        ]),

                                        RichEditor::make('post_content')
                                            ->live()
                                            ->json()
                                            ->plugins([
                                                SourceCodePlugin::make(),
                                                EnhancedImagePlugin::make(),
                                            ])
                                            ->fileAttachmentsDirectory('posts/images')
                                            ->fileAttachmentsVisibility('public')
                                            ->toolbarButtons([
                                                // Text Formatting
                                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'small', 'lead', 'highlight', 'textColor'],

                                                // Headings & Alignment
                                                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],

                                                // Lists & Structure
                                                ['bulletList', 'orderedList', 'blockquote', 'codeBlock', 'horizontalRule'],

                                                // Advanced Elements
                                                //                                                ['link', 'table', 'enhancedImage', 'details', 'attachFiles'],
                                                ['link', 'table', 'enhancedImage', 'details'],

                                                // Layout & Grid
                                                ['grid', 'gridDelete'],

                                                // Merge Tags (if using)
                                                ['mergeTags'],

                                                // Actions
                                                ['undo', 'redo', 'clearFormatting', 'sourceCode'],
                                            ])
                                            ->floatingToolbars([
                                                'paragraph' => [
                                                    'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'small', 'lead', 'textColor',
                                                ],
                                                'heading' => [
                                                    'h1', 'h2', 'h3',
                                                ],
                                                'table' => [
                                                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                                                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                                                    'tableMergeCells', 'tableSplitCell',
                                                    'tableToggleHeaderRow',
                                                    'tableDelete',
                                                ],
                                                'image' => [
                                                    'enhancedImage',
                                                ],

                                            ])

                                            ->label('Content')
                                            ->extraInputAttributes(['style' => 'min-height: 16rem;'])
                                            ->afterStateUpdated(function ($state, $record): void {
                                                if ($record) {
                                                    // Calculate read time based on content
                                                    $readTime = self::calculateReadTime($state);
                                                    $record->setMeta('read_time', $readTime);
                                                    $record->save();
                                                }
                                            }),
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
                                            ->timezone(fn (GeneralSettings $settings) => $settings->timezone) // TODO: handle UNKNOWN TIME ZONE
                                            ->default(now())
                                            ->required(),

                                        Select::make('post_author_id')
                                            ->relationship('author', 'name')
                                            ->searchable()
                                            ->required()
                                            ->default(fn () => auth()->id())
                                            ->label('Author'),

                                        // TODO: FIX OR REPLACE
                                        TextEntry::make('read_time')
                                            ->label('Read Time')
                                            ->icon('heroicon-o-clock')
                                            ->html(function ($record): string {
                                                //                                                if ($record) {
                                                //                                                    $readTime = $record->getMeta('read_time', '');
                                                //                                                    return $readTime ? "{$readTime} minutes" : 'Not calculated yet';
                                                //                                                }
                                                return '- Not calculated yet';
                                            }),

                                    ]),

                                Section::make('Featured Image')
                                    ->description('')
                                    ->columnSpanFull()
                                    ->schema([

                                        // Preview Image
                                        SpatieMediaLibraryImageEntry::make('featured_image')
                                            ->hiddenLabel()
                                            ->extraImgAttributes(['class' => 'rounded-md'])
                                            ->imageWidth('inherit')
                                            ->imageHeight('inherit')
                                            ->collection('featured')
                                            ->hidden(fn (?Post $record): bool => ! ($record?->hasMedia('featured') ?? false)),

                                        // Fallback image if preview is un available
                                        View::make('franken-cms::components.image-placeholder')
                                            ->hidden(fn (?Post $record): bool => ($record?->hasMedia('featured') ?? false)),

                                        Actions::make([
                                            Action::make('edit_featured_image_details')
                                                ->label(__('Update Image'))
                                                ->icon('heroicon-o-pencil-square')
                                                ->color('gray')
                                                ->size('sm')
                                                ->modalHeading(__('Featured Image Details'))
                                                ->modalDescription(__('Configure accessibility, display options, and metadata for your featured image.'))
                                                ->modalWidth(Width::ThreeExtraLarge)
                                                ->fillForm(fn (?array $arguments, callable $get): array => [
                                                    'modal_featured_image_alt'          => $get('featured_image_alt') ?? '',
                                                    'modal_featured_image_caption'      => $get('featured_image_caption') ?? '',
                                                    'modal_featured_image_attribution'  => $get('featured_image_attribution') ?? '',
                                                    'modal_featured_image_css'          => $get('featured_image_css') ?? '',
                                                    'modal_featured_image_lazy_loading' => $get('featured_image_lazy_loading') ?? true,
                                                    'modal_featured_image_width'        => $get('featured_image_width'),
                                                    'modal_featured_image_height'       => $get('featured_image_height'),
                                                    'modal_featured_image_focal_x'      => $get('featured_image_focal_x') ?? 50,
                                                    'modal_featured_image_focal_y'      => $get('featured_image_focal_y') ?? 50,
                                                ])
                                                ->schema([

                                                    SpatieMediaLibraryFileUpload::make('featured_image')
                                                        ->label(__('Featured Image'))
                                                        ->collection('featured')
                                                        ->disk('public') // todo: make configurable
                                                        ->image()
                                                        ->imageEditor()
                                                        ->previewable()
                                                        ->maxSize(10240) // 10MB TODO: make configurable
                                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                                        ->visibility('public')
                                                        ->multiple(false)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, callable $set) {
                                                            if ($state) {
                                                                // Reset focal point to center for new uploads
                                                                $set('featured_image_focal_x', 50);
                                                                $set('featured_image_focal_y', 50);

                                                                // Auto-populate width and height from uploaded file
                                                                $dimensions = static::getImageDimensions($state);
                                                                if ($dimensions) {
                                                                    $set('featured_image_width', $dimensions['width']);
                                                                    $set('featured_image_height', $dimensions['height']);
                                                                }
                                                            }
                                                        }),

                                                ]),

                                        ]),

                                    ]),

                            ]),
                    ]),
            ]);
    }

    /**
     * Calculate the estimated read time for a post based on its content
     *
     * @param  array|string  $content  The post content in JSON format
     * @return int Estimated read time in minutes
     */
    private static function calculateReadTime(array | string $content): int
    {
        return PostHelper::calculate_read_time(PostHelper::convert_tip_tap_to_plain_text($content));

    }
}
