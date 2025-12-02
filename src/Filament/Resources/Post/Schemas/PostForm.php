<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Post\Schemas;

use Exception;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Enums\PostType;
use FrankenCms\Filament\Actions\GenerateAltTextAction;
use FrankenCms\Filament\Actions\GenerateBlogPostAction;
use FrankenCms\Filament\Actions\GenerateImageTitleAction;
use FrankenCms\Filament\Actions\GenerateTeaserAction;
use FrankenCms\Filament\Actions\GenerateTitleAction;
use FrankenCms\Filament\Forms\Components\FocalPointPicker;
use FrankenCms\Filament\Forms\Components\TitleWithSlugInput;
use FrankenCms\Filament\Plugins\RichEditor\EnhancedImagePlugin;
use FrankenCms\Filament\Plugins\RichEditor\SourceCodePlugin;
use FrankenCms\Filament\Resources\Concerns\HasSeoFields;
use FrankenCms\Helpers\PostHelper;
use FrankenCms\Helpers\TemplateHelpers;
use FrankenCms\Models\Post;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\MediaSettings;
use FrankenCms\Settings\ReadingSettings;
use Livewire\Component;

class PostForm
{
    use HasSeoFields;

    public static function make(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);
        $readingSettings = app(ReadingSettings::class);
        $mediaSettings = app(MediaSettings::class);

        return $schema
            ->components([
                Tabs::make('Post Editor')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        // Content Tab
                        Tab::make('Content')
                            ->icon('heroicon-o-document-text')
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
                                    titleHintAction: GenerateTitleAction::make('generate_blog_post_title'),
                                ),

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
                                    })
                                    ->hintAction(GenerateTeaserAction::make('generate_teaser')),

                                RichEditor::make('post_content')
                                    ->live()
                                    ->json()
                                    ->plugins([
                                        SourceCodePlugin::make(),
                                        EnhancedImagePlugin::make(),
                                    ])
                                    ->fileAttachmentsDirectory('posts/images')
                                    ->fileAttachmentsVisibility('public')
                                    ->hintAction(GenerateBlogPostAction::make('generate_blog_post'))
                                    ->toolbarButtons([
                                        // Text Formatting
                                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'small', 'lead', 'highlight', 'textColor'],

                                        // Headings & Alignment
                                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],

                                        // Lists & Structure
                                        ['bulletList', 'orderedList', 'blockquote', 'codeBlock', 'horizontalRule'],

                                        // Advanced Elements
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

                        // Settings Tab
                        Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Publishing')
                                    ->schema([
                                        Select::make('post_status')
                                            ->label('Status')
                                            ->selectablePlaceholder(false)
                                            ->options(PostStatus::class)
                                            ->default(PostStatus::DRAFT)
                                            ->columnSpan(1),

                                        DateTimePicker::make('post_published_at')
                                            ->label('Publish Date')
                                            ->timezone(fn (GeneralSettings $settings) => $settings->timezone)
                                            ->default(now())
                                            ->required()
                                            ->columnSpan(1),

                                        Select::make('post_author_id')
                                            ->relationship('author', 'name')
                                            ->searchable()
                                            ->required()
                                            ->default(fn () => auth()->id())
                                            ->label('Author')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Template')
                                    ->schema([
                                        Select::make('template')
                                            ->label('Post Template')
                                            ->options(fn () => self::getTemplates())
                                            ->searchable()
                                            ->default('post')
                                            ->placeholder('Select a template')
                                            ->helperText('Optional: Use a specific template for this post. Defaults to "post" template.'),
                                    ]),

                                Section::make('Organization')
                                    ->schema([
                                        Select::make('categories')
                                            ->label('Categories')
                                            ->relationship(
                                                name: 'terms',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query) => $query->whereHas('taxonomy', fn ($q) => $q->where('name', 'category'))
                                            )
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique('terms', 'slug', ignoreRecord: true),
                                                Textarea::make('description')
                                                    ->rows(2),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                $taxonomy = \FrankenCms\Models\Taxonomy::where('name', 'category')->first();
                                                $term = \FrankenCms\Models\Term::create([
                                                    ...$data,
                                                    'taxonomy_id' => $taxonomy->id,
                                                ]);
                                                return $term->id;
                                            })
                                            ->columnSpanFull(),

                                        Select::make('tags')
                                            ->label('Tags')
                                            ->relationship(
                                                name: 'terms',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query) => $query->whereHas('taxonomy', fn ($q) => $q->where('name', 'tag'))
                                            )
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique('terms', 'slug', ignoreRecord: true),
                                                Textarea::make('description')
                                                    ->rows(2),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                $taxonomy = \FrankenCms\Models\Taxonomy::where('name', 'tag')->first();
                                                $term = \FrankenCms\Models\Term::create([
                                                    ...$data,
                                                    'taxonomy_id' => $taxonomy->id,
                                                ]);
                                                return $term->id;
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Metadata')
                                    ->schema([
                                        TextEntry::make('read_time')
                                            ->label('Read Time')
                                            ->icon('heroicon-o-clock')
                                            ->html(function ($record): string {
                                                return '- Not calculated yet';
                                            }),
                                    ]),
                            ]),

                        // Featured Image Tab
                        Tab::make('Featured Image')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('featured_image')
                                            ->label(__('Featured Image'))
                                            ->collection('featured')
//                                            ->disk('public')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(function () use ($mediaSettings) {
                                                $ratio = $mediaSettings->featured_aspect_ratio;

                                                if ($ratio === 'custom' && $mediaSettings->featured_custom_width && $mediaSettings->featured_custom_height) {
                                                    $ratio = aspect_ratio($mediaSettings->featured_custom_width, $mediaSettings->featured_custom_height);
                                                }
                                                return [
                                                    $ratio,
                                                    null,
                                                ];
                                            })
                                            ->previewable()
                                            ->maxSize(10240)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                            ->visibility('public')
                                            ->multiple(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, Component $livewire) {
                                                if (! $state) {
                                                    return;
                                                }

                                                if (is_object($state)) {
                                                    $dimensions = PostHelper::get_image_dimensions($state);
                                                    if ($dimensions) {
                                                        $set('featured_image_width', $dimensions['width']);
                                                        $set('featured_image_height', $dimensions['height']);
                                                    }

                                                    $set('featured_image_focal_point', '50% 50%');

                                                    try {
                                                        if (method_exists($state, 'temporaryUrl')) {
                                                            $imageUrl = $state->temporaryUrl();
                                                        } elseif (method_exists($state, 'getTemporaryUrl')) {
                                                            $imageUrl = $state->getTemporaryUrl();
                                                        } elseif (method_exists($state, 'getRealPath')) {
                                                            $imageUrl = 'data:image/' . $state->getClientOriginalExtension() . ';base64,' . base64_encode(file_get_contents($state->getRealPath()));
                                                        } else {
                                                            return;
                                                        }

                                                        $livewire->dispatch('featuredImageUploaded', [
                                                            'imageUrl' => $imageUrl,
                                                            'focalX'   => 50,
                                                            'focalY'   => 50,
                                                        ]);
                                                    } catch (Exception $e) {
                                                        // Silent fail
                                                    }
                                                }
                                            })
                                            ->afterStateHydrated(function ($component, $state, ?Post $record, Component $livewire): void {
                                                if (! $record || ! $record->hasMedia('featured')) {
                                                    return;
                                                }

                                                $media = $record->getFirstMedia('featured');
                                                $focalPoint = $media->getCustomProperty('focal_point', ['x' => 50, 'y' => 50]);

                                                // Dispatch event to Alpine component with existing image
                                                $livewire->dispatch('featuredImageUploaded', [
                                                    'imageUrl' => $media->getUrl(),
                                                    'focalX'   => $focalPoint['x'] ?? 50,
                                                    'focalY'   => $focalPoint['y'] ?? 50,
                                                ]);
                                            }),

                                        FocalPointPicker::make('featured_image_focal_point')
                                            ->label(__('Focal Point'))
                                            ->imageField('featured_image')
                                            ->collection('featured')
                                            ->live()
                                            ->columnSpanFull()
                                            ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                if ($record && $record->hasMedia('featured')) {
                                                    $media = $record->getFirstMedia('featured');
                                                    $focalPoint = $media->getCustomProperty('focal_point', '50% 50%');
                                                    $component->state($focalPoint);
                                                }
                                            }),

                                        Section::make('Image Details')
                                            ->description('Essential information for accessibility and SEO')
                                            ->schema([
                                                TextInput::make('featured_image_alt')
                                                    ->label(__('Alt Text'))
                                                    ->placeholder(__('Describe what the image shows'))
                                                    ->helperText(__('Important for accessibility and SEO'))
                                                    ->maxLength(255)
                                                    ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                        if ($record && $record->hasMedia('featured')) {
                                                            $media = $record->getFirstMedia('featured');
                                                            $component->state($media->getCustomProperty('alt', ''));
                                                        }
                                                    })
                                                    ->hintAction(GenerateAltTextAction::make('generate_alt_text')),

                                                TextInput::make('featured_image_title')
                                                    ->label(__('Title'))
                                                    ->placeholder(__('Optional hover text'))
                                                    ->helperText(__('Shown when hovering over the image'))
                                                    ->maxLength(255)
                                                    ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                        if ($record && $record->hasMedia('featured')) {
                                                            $media = $record->getFirstMedia('featured');
                                                            $component->state($media->getCustomProperty('title', ''));
                                                        }
                                                    })
                                                    ->hintAction(GenerateImageTitleAction::make('generate_image_title')),
                                            ])
                                            ->columns(2),

                                        Tabs::make('Advanced Settings')
                                            ->tabs([
                                                Tab::make('Display Options')
                                                    ->schema([
                                                        TextInput::make('featured_image_css')
                                                            ->label(__('CSS Classes'))
                                                            ->placeholder(__('e.g., rounded shadow-lg mx-auto'))
                                                            ->helperText(__('Custom CSS classes for styling'))
                                                            ->maxLength(255)
                                                            ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                if ($record && $record->hasMedia('featured')) {
                                                                    $media = $record->getFirstMedia('featured');
                                                                    $component->state($media->getCustomProperty('css_classes', ''));
                                                                }
                                                            }),

                                                        Toggle::make('featured_image_lazy_loading')
                                                            ->label(__('Lazy Loading'))
                                                            ->helperText(__('Delays loading until image is in viewport'))
                                                            ->default(false)
                                                            ->inline()
                                                            ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                if ($record && $record->hasMedia('featured')) {
                                                                    $media = $record->getFirstMedia('featured');
                                                                    $component->state($media->getCustomProperty('lazy_loading', false));
                                                                }
                                                            }),

                                                        Select::make('featured_image_fetchpriority')
                                                            ->label(__('Fetch Priority'))
                                                            ->helperText(__('Hint to browser about resource priority'))
                                                            ->options([
                                                                'none' => __('None (default)'),
                                                                'high' => __('High'),
                                                                'low'  => __('Low'),
                                                            ])
                                                            ->default('none')
                                                            ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                if ($record && $record->hasMedia('featured')) {
                                                                    $media = $record->getFirstMedia('featured');
                                                                    $component->state($media->getCustomProperty('fetchpriority', 'none'));
                                                                }
                                                            }),

                                                        Grid::make(2)
                                                            ->schema([
                                                                TextInput::make('featured_image_width')
                                                                    ->label(__('Width'))
                                                                    ->numeric()
                                                                    ->suffix('px')
                                                                    ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                        if ($record && $record->hasMedia('featured')) {
                                                                            $media = $record->getFirstMedia('featured');
                                                                            $component->state($media->getCustomProperty('width', null));
                                                                        }
                                                                    }),

                                                                TextInput::make('featured_image_height')
                                                                    ->label(__('Height'))
                                                                    ->numeric()
                                                                    ->suffix('px')
                                                                    ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                        if ($record && $record->hasMedia('featured')) {
                                                                            $media = $record->getFirstMedia('featured');
                                                                            $component->state($media->getCustomProperty('height', null));
                                                                        }
                                                                    }),
                                                            ]),
                                                    ]),

                                                Tab::make('Caption & Attribution')
                                                    ->schema([
                                                        Textarea::make('featured_image_caption')
                                                            ->label(__('Caption'))
                                                            ->placeholder(__('Optional caption text'))
                                                            ->helperText(__('Displayed below the image'))
                                                            ->maxLength(500)
                                                            ->rows(2)
                                                            ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                if ($record && $record->hasMedia('featured')) {
                                                                    $media = $record->getFirstMedia('featured');
                                                                    $component->state($media->getCustomProperty('caption', ''));
                                                                }
                                                            }),

                                                        TextInput::make('featured_image_attribution')
                                                            ->label(__('Attribution'))
                                                            ->placeholder(__('Photo credit or source'))
                                                            ->helperText(__('Credit the photographer or source'))
                                                            ->maxLength(255)
                                                            ->afterStateHydrated(function ($component, $state, ?Post $record): void {
                                                                if ($record && $record->hasMedia('featured')) {
                                                                    $media = $record->getFirstMedia('featured');
                                                                    $component->state($media->getCustomProperty('attribution', ''));
                                                                }
                                                            }),
                                                    ]),

                                            ]),
                                    ]),
                            ]),

                        // SEO Tab
                        self::getSeoTab(),
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

    private static function getTemplates(): array
    {
        return TemplateHelpers::getPostTemplates();
    }
}
