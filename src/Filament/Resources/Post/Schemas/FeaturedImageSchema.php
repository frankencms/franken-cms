<?php

namespace FrankenCms\Filament\Resources\Post\Schemas;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use FrankenCms\Filament\Forms\Components\FocalPointPicker;
use FrankenCms\Models\Post;
use Spatie\MediaLibrary\HasMedia;

class FeaturedImageSchema
{
    public static function make(string $collection = 'featured'): array
    {
        return [
            Group::make([

                //                Preview Image
                SpatieMediaLibraryImageEntry::make('featured_image')
                    ->hiddenLabel()
                    ->extraImgAttributes(['class' => 'rounded-md'])
                    ->imageWidth('inherit')
                    ->imageHeight('inherit')
                    ->collection($collection)
                    ->hidden(fn (Post $record): bool => ! $record->getFirstMedia($collection)),

                // Fallback image if preview is un available
                //                View::make('franken-cms::components.image-placeholder')
                //                    ->visible(fn (Get $get) => ! $get('featured_image')),

                //                SpatieMediaLibraryFileUpload::make('featured_image')
                //                    ->label(__('Featured Image'))
                //                    ->collection($collection)
                //                    ->disk('public') // todo: make configurable
                //                    ->image()
                //                    ->imageEditor()
                //                    ->previewable()
                //                    ->maxSize(10240) // 10MB TODO: make configurable
                //                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                //                    ->visibility('public')
                //                    ->multiple(false)
                //                    ->live()
                //                    ->afterStateUpdated(function ($state, callable $set) {
                //                        if ($state) {
                //                            // Reset focal point to center for new uploads
                //                            $set('featured_image_focal_point', '50% 50%');
                //
                //                            // Auto-populate width and height from uploaded file
                //                            $dimensions = static::getImageDimensions($state);
                //                            if ($dimensions) {
                //                                $set('featured_image_width', $dimensions['width']);
                //                                $set('featured_image_height', $dimensions['height']);
                //                            }
                //                        }
                //                    }),

                Actions::make([
                    Action::make('edit_featured_image_details')
                        ->label(__('Edit Image Details'))
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->size('sm')
                        ->modalHeading(__('Featured Image Details'))
                        ->modalDescription(__('Configure accessibility, display options, and metadata for your featured image.'))
                        ->modalWidth(Width::ThreeExtraLarge)
                        ->fillForm(fn (?array $arguments, callable $get): array => [
                            'modal_featured_image_alt'           => $get('featured_image_alt') ?? '',
                            'modal_featured_image_caption'       => $get('featured_image_caption') ?? '',
                            'modal_featured_image_attribution'   => $get('featured_image_attribution') ?? '',
                            'modal_featured_image_css'           => $get('featured_image_css') ?? '',
                            'modal_featured_image_lazy_loading'  => $get('featured_image_lazy_loading') ?? true,
                            'modal_featured_image_fetchpriority' => $get('featured_image_fetchpriority') ?? 'none',
                            'modal_featured_image_width'         => $get('featured_image_width'),
                            'modal_featured_image_height'        => $get('featured_image_height'),
                            'modal_featured_image_focal_point'   => $get('featured_image_focal_point') ?? '50% 50%',
                        ])
                        ->schema([

                            SpatieMediaLibraryFileUpload::make('featured_image')
                                ->label(__('Featured Image'))
                                ->collection($collection)
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
                                        $set('modal_featured_image_focal_point', '50% 50%');

                                        // Auto-populate width and height from uploaded file
                                        $dimensions = static::getImageDimensions($state);
                                        if ($dimensions) {
                                            $set('modal_featured_image_width', $dimensions['width']);
                                            $set('modal_featured_image_height', $dimensions['height']);
                                        }
                                    }
                                }),

                            FocalPointPicker::make('modal_featured_image_focal_point')
                                ->label(__('Focal Point'))
                                ->imageField('featured_image')
                                ->collection($collection)
                                ->live()
                                ->columnSpanFull(),

                            Hidden::make('modal_featured_image_focal_point')->default('50% 50%'),

                            Section::make('Image Details')
                                ->description('Essential information for accessibility and SEO')
                                ->schema([
                                    TextInput::make('modal_featured_image_alt')
                                        ->label(__('Alt Text'))
                                        ->placeholder(__('Describe what the image shows'))
                                        ->helperText(__('Important for accessibility and SEO'))
                                        ->maxLength(255),

                                    TextInput::make('modal_featured_image_caption')
                                        ->label(__('Caption'))
                                        ->placeholder(__('Optional caption text'))
                                        ->helperText(__('Displayed below the image'))
                                        ->maxLength(500),
                                ])
                                ->columns(2),

                            Tabs::make('Advanced Settings')
                                ->tabs([
                                    Tabs\Tab::make('Display Options')
                                        ->schema([
                                            TextInput::make('modal_featured_image_css')
                                                ->label(__('CSS Classes'))
                                                ->placeholder(__('e.g., rounded shadow-lg mx-auto'))
                                                ->helperText(__('Custom CSS classes for styling'))
                                                ->maxLength(255),

                                            Toggle::make('modal_featured_image_lazy_loading')
                                                ->label(__('Lazy Loading'))
                                                ->helperText(__('Delays loading until image is in viewport'))
                                                ->default(true)
                                                ->inline(),

                                            Select::make('modal_featured_image_fetchpriority')
                                                ->label(__('Fetch Priority'))
                                                ->helperText(__('Hint to browser about resource priority'))
                                                ->options([
                                                    'none' => __('None (default)'),
                                                    'high' => __('High'),
                                                    'low'  => __('Low'),
                                                ])
                                                ->default('none'),

                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('modal_featured_image_width')
                                                        ->label(__('Width'))
                                                        ->numeric()
                                                        ->suffix('px'),

                                                    TextInput::make('modal_featured_image_height')
                                                        ->label(__('Height'))
                                                        ->numeric()
                                                        ->suffix('px'),
                                                ]),
                                        ]),

                                    Tabs\Tab::make('Attribution')
                                        ->schema([
                                            TextInput::make('modal_featured_image_attribution')
                                                ->label(__('Attribution'))
                                                ->placeholder(__('Photo credit or source'))
                                                ->helperText(__('Credit the photographer or source'))
                                                ->maxLength(255),
                                        ]),

                                ]),
                        ])
                        ->action(function (array $data, callable $set): void {
                            // Update the main form fields with modal data
                            $set('featured_image_alt', $data['modal_featured_image_alt'] ?? '');
                            $set('featured_image_caption', $data['modal_featured_image_caption'] ?? '');
                            $set('featured_image_attribution', $data['modal_featured_image_attribution'] ?? '');
                            $set('featured_image_css', $data['modal_featured_image_css'] ?? '');
                            $set('featured_image_lazy_loading', $data['modal_featured_image_lazy_loading'] ?? true);
                            $set('featured_image_fetchpriority', $data['modal_featured_image_fetchpriority'] ?? 'none');
                            $set('featured_image_width', $data['modal_featured_image_width']);
                            $set('featured_image_height', $data['modal_featured_image_height']);
                            $set('featured_image_focal_point', $data['modal_featured_image_focal_point'] ?? '50% 50%');
                        }),
                ]),
            ])
                ->columnSpanFull(),

            // Hidden fields to store the actual data
            Hidden::make('featured_image_alt'),
            Hidden::make('featured_image_caption'),
            Hidden::make('featured_image_attribution'),
            Hidden::make('featured_image_css'),
            Hidden::make('featured_image_lazy_loading')->default(true),
            Hidden::make('featured_image_fetchpriority')->default('none'),
            Hidden::make('featured_image_width'),
            Hidden::make('featured_image_height'),
            Hidden::make('featured_image_focal_point')->default('50% 50%'),
        ];
    }

    /**
     * Save featured image metadata to media custom properties
     */
    public static function saveFeaturedImageMetadata(HasMedia $model, array $data, string $collection = 'featured'): void
    {
        $media = $model->getFirstMedia($collection);

        if ($media) {
            $customProperties = [
                'alt'           => $data['featured_image_alt'] ?? '',
                'caption'       => $data['featured_image_caption'] ?? '',
                'attribution'   => $data['featured_image_attribution'] ?? '',
                'focal_point'   => $data['featured_image_focal_point'] ?? '50% 50%',
                'width'         => $data['featured_image_width'] ?? null,
                'height'        => $data['featured_image_height'] ?? null,
                'css'           => $data['featured_image_css'] ?? '',
                'loading'       => isset($data['featured_image_lazy_loading']) && $data['featured_image_lazy_loading'] ? 'lazy' : 'eager',
                'fetchpriority' => $data['featured_image_fetchpriority'] ?? 'none',
            ];

            $media->setCustomProperty('featured_image_data', $customProperties);
            $media->save();
        }
    }

    /**
     * Load featured image metadata from media custom properties
     */
    public static function loadFeaturedImageMetadata(HasMedia $model, string $collection = 'featured'): array
    {
        $media = $model->getFirstMedia($collection);

        if (! $media) {
            return [];
        }

        $data = $media->getCustomProperty('featured_image_data', []);

        return [
            'featured_image_alt'           => $data['alt'] ?? '',
            'featured_image_caption'       => $data['caption'] ?? '',
            'featured_image_attribution'   => $data['attribution'] ?? '',
            'featured_image_focal_point'   => $data['focal_point'] ?? '50% 50%',
            'featured_image_width'         => $data['width'] ?? null,
            'featured_image_height'        => $data['height'] ?? null,
            'featured_image_css'           => $data['css'] ?? '',
            'featured_image_lazy_loading'  => ($data['loading'] ?? 'lazy') === 'lazy',
            'featured_image_fetchpriority' => $data['fetchpriority'] ?? 'none',
        ];
    }

    /**
     * Get the featured image data for a model
     */
    public static function getFeaturedImageData(HasMedia $model, string $collection = 'featured'): ?array
    {
        $media = $model->getFirstMedia($collection);

        if (! $media) {
            return null;
        }

        $customProperties = $media->getCustomProperty('featured_image_data', []);

        return [
            'url'           => $media->getUrl(),
            'alt'           => $customProperties['alt'] ?? '',
            'caption'       => $customProperties['caption'] ?? '',
            'attribution'   => $customProperties['attribution'] ?? '',
            'focal_point'   => $customProperties['focal_point'] ?? '50% 50%',
            'width'         => $customProperties['width'] ?? null,
            'height'        => $customProperties['height'] ?? null,
            'css'           => $customProperties['css'] ?? '',
            'loading'       => $customProperties['loading'] ?? 'lazy',
            'fetchpriority' => $customProperties['fetchpriority'] ?? 'none',
            'media'         => $media,
        ];
    }

    /**
     * Get image dimensions from a file path
     */
    protected static function getImageDimensions($file): ?array
    {
        try {
            if (is_string($file)) {
                $path = $file;
            } elseif (method_exists($file, 'getRealPath')) {
                $path = $file->getRealPath();
            } else {
                return null;
            }

            $imageInfo = getimagesize($path);
            if ($imageInfo !== false) {
                return [
                    'width'  => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (Exception $e) {
            // Silent fail - dimensions are optional
        }

        return null;
    }
}
