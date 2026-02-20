<?php

namespace FrankenCms\Filament\Plugins\RichEditor;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use FrankenCms\Filament\Forms\Components\FocalPointPicker;
use Illuminate\Support\Str;
use Livewire\Component;
use Storage;

class EnhancedImageAction
{
    public static function make(): Action
    {
        return Action::make('enhancedImage')
            ->label(__('Enhanced Image'))
            ->modalHeading(__('Insert Enhanced Image'))
            ->modalWidth(Width::ExtraLarge)
            ->fillForm(fn (array $arguments): array => [
                'alt'           => $arguments['alt'] ?? null,
                'title'         => $arguments['title'] ?? null,
                'caption'       => $arguments['caption'] ?? null,
                'attribution'   => $arguments['attribution'] ?? null,
                'loading'       => ($arguments['loading'] ?? 'lazy') === 'lazy',
                'fetchpriority' => $arguments['fetchpriority'] ?? 'none',
                'focal_point'   => $arguments['focal_point'] ?? '50% 50%',
                'width'         => $arguments['width'] ?? null,
                'height'        => $arguments['height'] ?? null,
                'css'           => $arguments['css'] ?? null,
            ])
            ->schema(fn (array $arguments, RichEditor $component): array => [
                FileUpload::make('file')
                    ->imageEditor()
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
                    ->afterStateUpdated(function ($state, Set $set, Component $livewire) {
                        if ($state) {
                            $dimensions = static::getImageDimensions($state);
                            if ($dimensions) {
                                $set('width', $dimensions['width']);
                                $set('height', $dimensions['height']);
                            }

                            // Reset focal point to center for new uploads
                            $set('focal_point', '50% 50%');

                            // Get the temporary URL for the uploaded file
                            $temporaryUrl = null;
                            try {
                                // For Livewire file uploads, the temporary URL method is available
                                if (is_object($state) && method_exists($state, 'temporaryUrl')) {
                                    $temporaryUrl = $state->temporaryUrl();
                                } elseif (is_string($state)) {
                                    // If it's already a string, it might be the temporary URL
                                    $temporaryUrl = $state;
                                }
                            } catch (Exception $e) {
                                // Silent fail - continue without temporary URL
                            }

                            // Dispatch Livewire event with the new image data
                            $livewire->dispatch('enhancedImageUploaded', [
                                'temporaryUrl' => $temporaryUrl,
                                'focalX'       => 50,
                                'focalY'       => 50,
                            ]);
                        }
                    }),

                Section::make('Image Details')
                    ->description('Essential information for accessibility and SEO')
                    ->schema([
                        TextInput::make('alt')
                            ->label(__('Alt Text'))
                            ->placeholder(__('Describe what the image shows'))
                            ->helperText(__('Important for accessibility and SEO'))
                            ->maxLength(255),

                        TextInput::make('title')
                            ->label(__('Title'))
                            ->placeholder(__('Optional hover text'))
                            ->helperText(__('Shown when hovering over the image'))
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Tabs::make('Additional Settings')
                    ->tabs([
                        Tabs\Tab::make('Display Options')
                            ->schema([
                                TextInput::make('css')
                                    ->label(__('CSS'))
                                    ->placeholder(__('e.g., rounded shadow-lg mx-auto'))
                                    ->helperText(__('Custom CSS classes (safelist if using a purge tool).'))
                                    ->maxLength(255),

                                Toggle::make('loading')
                                    ->label(__('Lazy Loading'))
                                    ->helperText(__('Delays loading until image is in viewport'))
                                    ->default(true)
                                    ->inline(),

                                Select::make('fetchpriority')
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
                                        TextInput::make('width')
                                            ->label(__('Width'))
                                            ->numeric()
                                            ->suffix('px')
                                            ->dehydrated(),

                                        TextInput::make('height')
                                            ->label(__('Height'))
                                            ->numeric()
                                            ->suffix('px')
                                            ->dehydrated(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Focal Point')
                            ->schema([
                                FocalPointPicker::make('focal_point')
                                    ->label(__('Focal Point'))
                                    ->image(fn (): ?string => $arguments['src'] ?? null)
                                    ->live()
                                    ->columnSpanFull(),

                                Hidden::make('focal_point')
                                    ->default($arguments['focal_point'] ?? '50% 50%'),
                            ]),

                        Tabs\Tab::make('Caption & Attribution')
                            ->schema([
                                Textarea::make('caption')
                                    ->label(__('Caption'))
                                    ->placeholder(__('Optional caption text'))
                                    ->helperText(__('Displayed below the image'))
                                    ->maxLength(500)
                                    ->rows(2),

                                TextInput::make('attribution')
                                    ->label(__('Attribution'))
                                    ->placeholder(__('Photo credit or source'))
                                    ->helperText(__('Credit the photographer or source'))
                                    ->maxLength(255),
                            ]),

                    ]),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component, Component $livewire): void {
                $fileData = $data['file'] ?? null;

                // Handle file upload - FileUpload with storeFiles(false) returns a TemporaryUploadedFile
                // It may be wrapped in an array
                if ($fileData) {
                    $file = is_array($fileData) ? ($fileData[0] ?? null) : $fileData;

                    if ($file) {
                        // Store the file permanently instead of using temporary URL
                        try {
                            // Store file in the configured directory using the component's configured disk
                            $directory = $component->getFileAttachmentsDirectory() ?? 'posts/images';
                            $diskName = $component->getFileAttachmentsDiskName() ?? config('franken-cms.media_disk_name', 'public');

                            // Generate a unique filename
                            $extension = $file->getClientOriginalExtension() ?: 'jpg';
                            $filename = Str::orderedUuid() . '.' . $extension;
                            $fullPath = $directory . '/' . $filename;

                            // Get the file contents from the temporary file
                            // TemporaryUploadedFile may be on a different disk than the target
                            $tempDisk = $file->disk ?? config('livewire.temporary_file_upload.disk');
                            $tempFilePath = $file->path();

                            // Read the file content using a stream for memory efficiency
                            $stream = Storage::disk($tempDisk)->readStream($tempFilePath);

                            if (! $stream) {
                                throw new Exception('Failed to read temporary file stream');
                            }

                            // Write to the target disk
                            $success = Storage::disk($diskName)->writeStream($fullPath, $stream);

                            if (is_resource($stream)) {
                                fclose($stream);
                            }

                            if (! $success) {
                                throw new Exception('Failed to write file to disk: ' . $diskName);
                            }

                            // Use the path as the id so Filament can verify the file exists
                            $id = $fullPath;

                            // Get the public URL for the stored file
                            $src = Storage::disk($diskName)->url($fullPath);
                        } catch (Exception $e) {
                            // Fallback to temporary URL if permanent storage fails
                            $id = (string) Str::orderedUuid();
                            data_set($livewire, "componentFileAttachments.{$component->getStatePath()}.{$id}", $file);
                            $src = $component->getUploadedFileAttachmentTemporaryUrl($file);

                            // Log the error for debugging
                            logger()->error('EnhancedImageAction: Failed to store file permanently', [
                                'error'     => $e->getMessage(),
                                'disk'      => $diskName ?? 'unknown',
                                'directory' => $directory ?? 'unknown',
                            ]);
                        }
                    }
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

    protected static function prepareImageAttributes(array $data, ?string $id, ?string $src): array
    {
        return [
            'id'            => $id,
            'src'           => $src,
            'alt'           => $data['alt'] ?? null,
            'title'         => $data['title'] ?? null,
            'caption'       => $data['caption'] ?? null,
            'attribution'   => $data['attribution'] ?? null,
            'loading'       => $data['loading'] ? 'lazy' : 'eager',
            'fetchpriority' => $data['fetchpriority'] ?? 'none',
            'focal_point'   => $data['focal_point'] ?? '50% 50%',
            'width'         => $data['width'] ?? null,
            'height'        => $data['height'] ?? null,
            'css'           => $data['css'] ?? null,
        ];
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
