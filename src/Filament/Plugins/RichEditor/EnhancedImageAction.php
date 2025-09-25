<?php

namespace FrankenCms\Filament\Plugins\RichEditor;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;
use Livewire\Component;
use Log;

class EnhancedImageAction
{
    public static function make(): Action
    {
        return Action::make('enhancedImage')
            ->label(__('Enhanced Image'))
            ->modalHeading(__('Insert Enhanced Image'))
            ->modalWidth(Width::ExtraLarge)
            ->fillForm(fn (array $arguments): array => [
                'alt'         => $arguments['alt'] ?? null,
                'title'       => $arguments['title'] ?? null,
                'caption'     => $arguments['caption'] ?? null,
                'attribution' => $arguments['attribution'] ?? null,
                'loading'     => ($arguments['loading'] ?? 'lazy') === 'lazy',
                'focal_x'     => $arguments['focal_x'] ?? 50,
                'focal_y'     => $arguments['focal_y'] ?? 50,
                'width'       => $arguments['width'] ?? null,
                'height'      => $arguments['height'] ?? null,
            ])
            ->schema(fn (array $arguments, RichEditor $component): array => [
                Section::make('Image Upload')
                    ->schema([
                        FileUpload::make('file')
                            ->previewable(false)
                            ->label(filled($arguments['src'] ?? null)
                                ? __('Replace Image')
                                : __('Upload Image'))
                            ->acceptedFileTypes($component->getFileAttachmentsAcceptedFileTypes())
                            ->maxSize($component->getFileAttachmentsMaxSize())
                            ->storeFiles(false)
                            ->required(blank($arguments['src'] ?? null))
                            ->hiddenLabel(blank($arguments['src'] ?? null))
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $dimensions = static::getImageDimensions($state);
                                    if ($dimensions) {
                                        $set('width', $dimensions['width']);
                                        $set('height', $dimensions['height']);
                                    }
                                }
                            }),
                    ]),

                Section::make('Image Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('alt')
                                    ->label(__('Alt Text'))
                                    ->helperText(__('Describe the image for accessibility'))
                                    ->maxLength(255),

                                TextInput::make('title')
                                    ->label(__('Title'))
                                    ->helperText(__('Image title (shown on hover)'))
                                    ->maxLength(255),
                            ]),

                        Textarea::make('caption')
                            ->label(__('Caption'))
                            ->helperText(__('Caption displayed below the image'))
                            ->maxLength(500)
                            ->rows(2),

                        TextInput::make('attribution')
                            ->label(__('Attribution'))
                            ->helperText(__('Photo credit or source'))
                            ->maxLength(255),
                    ]),

                Section::make('Display Settings')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('loading')
                                    ->label(__('Lazy Loading'))
                                    ->helperText(__('Load image when it comes into view'))
                                    ->default(true),

                                TextInput::make('width')
                                    ->label(__('Width'))
                                    ->numeric()
                                    ->suffix('px')
                                    ->readonly(),

                                TextInput::make('height')
                                    ->label(__('Height'))
                                    ->numeric()
                                    ->suffix('px')
                                    ->readonly(),
                            ]),

                        Hidden::make('focal_x')
                            ->default($arguments['focal_x'] ?? 50),

                        Hidden::make('focal_y')
                            ->default($arguments['focal_y'] ?? 50),

                        static::makeFocalPointComponent($arguments),
                    ]),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component, Component $livewire): void {

                // Debug: Log all the data being received
                Log::info('EnhancedImageAction data received:', [
                    'arguments' => $arguments,
                    'data'      => $data,
                    'focal_x'   => $data['focal_x'] ?? 'not set',
                    'focal_y'   => $data['focal_y'] ?? 'not set',
                ]);

                if ($data['file'] ?? null) {
                    $id = (string) Str::orderedUuid();

                    data_set($livewire, "componentFileAttachments.{$component->getStatePath()}.{$id}", $data['file']);
                    $src = $component->getUploadedFileAttachmentTemporaryUrl($data['file']);
                }

                if (filled($arguments['src'] ?? null)) {
                    // Fixes an issue where the editor selection is sent as text instead of a node,
                    // which causes the image update to fail when though the image is selected.
                    if ($arguments['editorSelection']['type'] !== 'node') {
                        $arguments['editorSelection']['type'] = 'node';
                        $arguments['editorSelection']['anchor']--;

                        unset($arguments['editorSelection']['head']);
                    }
                    $id ??= $arguments['id'] ?? null;
                    $src ??= $arguments['src'];

                    $component->runCommands(
                        [
                            EditorCommand::make('updateAttributes', arguments: [
                                'image',
                                static::prepareImageAttributes($data, $id, $src),
                            ]),
                        ],
                        editorSelection: $arguments['editorSelection'],
                    );

                    return;
                }

                if (blank($id ?? null) || blank($src ?? null)) {
                    return;
                }

                $component->runCommands(
                    [
                        EditorCommand::make('insertContent', arguments: [[
                            'type'  => 'image',
                            'attrs' => static::prepareImageAttributes($data, $id, $src),
                        ]]),
                    ],
                    editorSelection: $arguments['editorSelection'],
                );

            });

    }

    protected static function makeFocalPointComponent(array $arguments = []): View
    {
        return View::make('franken-cms::components.focal-point-picker')
            ->viewData([
                'statePaths' => [
                    'focal_x' => 'data.focal_x',
                    'focal_y' => 'data.focal_y',
                ],
                'existingImageSrc' => $arguments['src'] ?? null,
                'existingFocalX'   => $arguments['focal_x'] ?? 50,
                'existingFocalY'   => $arguments['focal_y'] ?? 50,
            ]);
    }

    protected static function prepareImageAttributes(array $data, ?string $id, ?string $src): array
    {
        $attributes = [
            'id'          => $id,
            'src'         => $src,
            'alt'         => $data['alt'] ?? null,
            'title'       => $data['title'] ?? null,
            'caption'     => $data['caption'] ?? null,
            'attribution' => $data['attribution'] ?? null,
            'loading'     => $data['loading'] ? 'lazy' : 'eager',
            'focal_x'     => $data['focal_x'] ?? 50,
            'focal_y'     => $data['focal_y'] ?? 50,
            'width'       => $data['width'] ?? null,
            'height'      => $data['height'] ?? null,
        ];

        Log::info('Prepared image attributes:', $attributes);

        return $attributes;
    }

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
