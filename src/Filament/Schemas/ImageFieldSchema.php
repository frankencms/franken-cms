<?php

namespace FrankenCms\Filament\Schemas;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Spatie\MediaLibrary\HasMedia;

class ImageFieldSchema
{
    /**
     * Create a complete image field with metadata support
     *
     * @param  string  $fieldName  The base field name (e.g., 'hero_image')
     * @param  string  $collection  The media collection name
     * @param  array  $options  Additional configuration options
     */
    public static function make(
        string $fieldName,
        string $collection,
        array $options = []
    ): array {
        // Normalize field name - replace dots with underscores for form field names
        $normalizedFieldName = str_replace('.', '_', $fieldName);

        $label = $options['label'] ?? str($fieldName)->title()->replace('_', ' ')->toString();
        $description = $options['description'] ?? null;
        $maxSize = $options['maxSize'] ?? 10240; // 10MB default
        $disk = $options['disk'] ?? 'public';
        $visibility = $options['visibility'] ?? 'public';

        $acceptedFileTypes = $options['acceptedFileTypes'] ?? [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ];

        return [
            Group::make([
                // Preview Image
                SpatieMediaLibraryImageEntry::make($normalizedFieldName)
                    ->hiddenLabel()
                    ->extraImgAttributes(['class' => 'rounded-md'])
                    ->imageWidth('inherit')
                    ->imageHeight('inherit')
                    ->collection($collection)
                    ->hidden(fn ($record): bool => ! $record || ! $record->getFirstMedia($collection)),

                // Edit Image Details Button
                Actions::make([
                    Action::make("edit_{$normalizedFieldName}_details")
                        ->label(__('Edit Image Details'))
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->size('sm')
                        ->modalHeading($label.' '.__('Details'))
                        ->modalDescription(__('Configure accessibility, display options, and metadata for your image.'))
                        ->modalWidth(Width::ThreeExtraLarge)
                        ->fillForm(fn (?array $arguments, callable $get): array => [
                            "modal_{$normalizedFieldName}_alt" => $get("{$normalizedFieldName}_alt") ?? '',
                            "modal_{$normalizedFieldName}_title" => $get("{$normalizedFieldName}_title") ?? '',
                            "modal_{$normalizedFieldName}_caption" => $get("{$normalizedFieldName}_caption") ?? '',
                            "modal_{$normalizedFieldName}_attribution" => $get("{$normalizedFieldName}_attribution") ?? '',
                            "modal_{$normalizedFieldName}_css" => $get("{$normalizedFieldName}_css") ?? '',
                            "modal_{$normalizedFieldName}_lazy_loading" => $get("{$normalizedFieldName}_lazy_loading") ?? true,
                            "modal_{$normalizedFieldName}_width" => $get("{$normalizedFieldName}_width"),
                            "modal_{$normalizedFieldName}_height" => $get("{$normalizedFieldName}_height"),
                            "modal_{$normalizedFieldName}_focal_x" => $get("{$normalizedFieldName}_focal_x") ?? 50,
                            "modal_{$normalizedFieldName}_focal_y" => $get("{$normalizedFieldName}_focal_y") ?? 50,
                        ])
                        ->schema([
                            SpatieMediaLibraryFileUpload::make($normalizedFieldName)
                                ->label($label)
                                ->collection($collection)
                                ->disk($disk)
                                ->image()
                                ->imageEditor()
                                ->previewable()
                                ->maxSize($maxSize)
                                ->acceptedFileTypes($acceptedFileTypes)
                                ->visibility($visibility)
                                ->multiple(false)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) use ($normalizedFieldName) {
                                    if ($state) {
                                        // Reset focal point to center for new uploads
                                        $set("{$normalizedFieldName}_focal_x", 50);
                                        $set("{$normalizedFieldName}_focal_y", 50);

                                        // Auto-populate width and height from uploaded file
                                        $dimensions = static::getImageDimensions($state);
                                        if ($dimensions) {
                                            $set("{$normalizedFieldName}_width", $dimensions['width']);
                                            $set("{$normalizedFieldName}_height", $dimensions['height']);
                                        }
                                    }
                                }),

                            Section::make('Image Details')
                                ->description('Essential information for accessibility and SEO')
                                ->schema([
                                    TextInput::make("modal_{$normalizedFieldName}_alt")
                                        ->label(__('Alt Text'))
                                        ->placeholder(__('Describe what the image shows'))
                                        ->helperText(__('Important for accessibility and SEO'))
                                        ->maxLength(255),

                                    TextInput::make("modal_{$normalizedFieldName}_title")
                                        ->label(__('Title'))
                                        ->placeholder(__('Title attribute for the image'))
                                        ->helperText(__('Displayed as tooltip on hover'))
                                        ->maxLength(255),

                                    TextInput::make("modal_{$normalizedFieldName}_caption")
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
                                            TextInput::make("modal_{$normalizedFieldName}_css")
                                                ->label(__('CSS Classes'))
                                                ->placeholder(__('e.g., rounded shadow-lg mx-auto'))
                                                ->helperText(__('Custom CSS classes for styling'))
                                                ->maxLength(255),

                                            Toggle::make("modal_{$normalizedFieldName}_lazy_loading")
                                                ->label(__('Lazy Loading'))
                                                ->helperText(__('Delays loading until image is in viewport'))
                                                ->default(true)
                                                ->inline(),

                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make("modal_{$normalizedFieldName}_width")
                                                        ->label(__('Width'))
                                                        ->numeric()
                                                        ->suffix('px'),

                                                    TextInput::make("modal_{$normalizedFieldName}_height")
                                                        ->label(__('Height'))
                                                        ->numeric()
                                                        ->suffix('px'),
                                                ]),
                                        ]),

                                    Tabs\Tab::make('Attribution')
                                        ->schema([
                                            TextInput::make("modal_{$normalizedFieldName}_attribution")
                                                ->label(__('Attribution'))
                                                ->placeholder(__('Photo credit or source'))
                                                ->helperText(__('Credit the photographer or source'))
                                                ->maxLength(255),
                                        ]),

                                    Tabs\Tab::make('Focal Point')
                                        ->schema([
                                            Hidden::make("modal_{$normalizedFieldName}_focal_x")
                                                ->default(50),

                                            Hidden::make("modal_{$normalizedFieldName}_focal_y")
                                                ->default(50),

                                            static::makeFocalPointComponent($collection, $normalizedFieldName, 'modal_'),
                                        ]),
                                ]),
                        ])
                        ->action(function (array $data, callable $set) use ($normalizedFieldName): void {
                            // Update the main form fields with modal data
                            $set("{$normalizedFieldName}_alt", $data["modal_{$normalizedFieldName}_alt"] ?? '');
                            $set("{$normalizedFieldName}_title", $data["modal_{$normalizedFieldName}_title"] ?? '');
                            $set("{$normalizedFieldName}_caption", $data["modal_{$normalizedFieldName}_caption"] ?? '');
                            $set("{$normalizedFieldName}_attribution", $data["modal_{$normalizedFieldName}_attribution"] ?? '');
                            $set("{$normalizedFieldName}_css", $data["modal_{$normalizedFieldName}_css"] ?? '');
                            $set("{$normalizedFieldName}_lazy_loading", $data["modal_{$normalizedFieldName}_lazy_loading"] ?? true);
                            $set("{$normalizedFieldName}_width", $data["modal_{$normalizedFieldName}_width"] ?? null);
                            $set("{$normalizedFieldName}_height", $data["modal_{$normalizedFieldName}_height"] ?? null);
                            $set("{$normalizedFieldName}_focal_x", $data["modal_{$normalizedFieldName}_focal_x"] ?? 50);
                            $set("{$normalizedFieldName}_focal_y", $data["modal_{$normalizedFieldName}_focal_y"] ?? 50);
                        }),
                ]),
            ])
                ->columnSpanFull(),

            // Hidden fields to store the actual data
            Hidden::make("{$normalizedFieldName}_alt"),
            Hidden::make("{$normalizedFieldName}_title"),
            Hidden::make("{$normalizedFieldName}_caption"),
            Hidden::make("{$normalizedFieldName}_attribution"),
            Hidden::make("{$normalizedFieldName}_css"),
            Hidden::make("{$normalizedFieldName}_lazy_loading")->default(true),
            Hidden::make("{$normalizedFieldName}_width"),
            Hidden::make("{$normalizedFieldName}_height"),
            Hidden::make("{$normalizedFieldName}_focal_x")->default(50),
            Hidden::make("{$normalizedFieldName}_focal_y")->default(50),
        ];
    }

    /**
     * Save image metadata to media custom properties
     */
    public static function saveImageMetadata(
        HasMedia $model,
        string $fieldName,
        array $data,
        string $collection
    ): void {
        $media = $model->getFirstMedia($collection);

        if ($media) {
            $customProperties = [
                'alt' => $data["{$fieldName}_alt"] ?? '',
                'title' => $data["{$fieldName}_title"] ?? '',
                'caption' => $data["{$fieldName}_caption"] ?? '',
                'attribution' => $data["{$fieldName}_attribution"] ?? '',
                'focal_x' => $data["{$fieldName}_focal_x"] ?? 50,
                'focal_y' => $data["{$fieldName}_focal_y"] ?? 50,
                'width' => $data["{$fieldName}_width"] ?? null,
                'height' => $data["{$fieldName}_height"] ?? null,
                'css' => $data["{$fieldName}_css"] ?? '',
                'loading' => isset($data["{$fieldName}_lazy_loading"]) && $data["{$fieldName}_lazy_loading"] ? 'lazy' : 'eager',
            ];

            $media->setCustomProperty("{$fieldName}_data", $customProperties);
            $media->save();
        }
    }

    /**
     * Load image metadata from media custom properties
     */
    public static function loadImageMetadata(
        HasMedia $model,
        string $fieldName,
        string $collection
    ): array {
        $media = $model->getFirstMedia($collection);

        if (! $media) {
            return [];
        }

        $data = $media->getCustomProperty("{$fieldName}_data", []);

        return [
            "{$fieldName}_alt" => $data['alt'] ?? '',
            "{$fieldName}_title" => $data['title'] ?? '',
            "{$fieldName}_caption" => $data['caption'] ?? '',
            "{$fieldName}_attribution" => $data['attribution'] ?? '',
            "{$fieldName}_focal_x" => $data['focal_x'] ?? 50,
            "{$fieldName}_focal_y" => $data['focal_y'] ?? 50,
            "{$fieldName}_width" => $data['width'] ?? null,
            "{$fieldName}_height" => $data['height'] ?? null,
            "{$fieldName}_css" => $data['css'] ?? '',
            "{$fieldName}_lazy_loading" => ($data['loading'] ?? 'lazy') === 'lazy',
        ];
    }

    /**
     * Get the image data for a model
     */
    public static function getImageData(
        HasMedia $model,
        string $fieldName,
        string $collection
    ): ?array {
        $media = $model->getFirstMedia($collection);

        if (! $media) {
            return null;
        }

        $customProperties = $media->getCustomProperty("{$fieldName}_data", []);

        return [
            'url' => $media->getUrl(),
            'alt' => $customProperties['alt'] ?? '',
            'title' => $customProperties['title'] ?? '',
            'caption' => $customProperties['caption'] ?? '',
            'attribution' => $customProperties['attribution'] ?? '',
            'focal_x' => $customProperties['focal_x'] ?? 50,
            'focal_y' => $customProperties['focal_y'] ?? 50,
            'width' => $customProperties['width'] ?? null,
            'height' => $customProperties['height'] ?? null,
            'css' => $customProperties['css'] ?? '',
            'loading' => $customProperties['loading'] ?? 'lazy',
            'media' => $media,
        ];
    }

    protected static function makeFocalPointComponent(
        string $collection,
        string $fieldName,
        string $prefix = ''
    ): View {
        $focalXField = $prefix.$fieldName.'_focal_x';
        $focalYField = $prefix.$fieldName.'_focal_y';

        return View::make('franken-cms::components.focal-point-picker')
            ->viewData([
                'statePaths' => [
                    'focal_x' => "data.{$focalXField}",
                    'focal_y' => "data.{$focalYField}",
                ],
                'existingImageSrc' => null, // Will be populated by JS from uploaded file
                'existingFocalX' => 50,
                'existingFocalY' => 50,
                'collection' => $collection,
                'fieldPrefix' => $prefix,
                'isFeaturedImage' => false, // This is a custom field, not featured image
                'fieldName' => $fieldName,
            ]);
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
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (Exception $e) {
            // Silent fail - dimensions are optional
        }

        return null;
    }
}
