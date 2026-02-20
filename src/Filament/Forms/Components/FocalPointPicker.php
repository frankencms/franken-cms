<?php

namespace FrankenCms\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class FocalPointPicker extends Field
{
    public ?Closure $image = null;

    protected string | Closure | null $collection = null;

    protected string $view = 'franken-cms::filament.fields.focal-point-picker';

    protected bool $hasDefaultState = true;

    public function imageField(string $field): static
    {
        return $this->image(image: function (Get $get) use ($field) {
            // First, check if we're editing an existing record with media
            // This takes priority over temporary uploads to show the actual saved image
            if ($this->collection) {
                $record = $this->getRecord();
                $collection = $this->evaluate($this->collection);

                if ($record && $record->exists && $collection && method_exists($record, 'getFirstMediaUrl')) {
                    $url = $record->getFirstMediaUrl($collection);
                    if ($url) {
                        return $url;
                    }
                }
            }

            // If no existing media, try to get the image state from the form (for new uploads)
            $imageState = $get($field);

            // Handle if it's wrapped in an array/collection (SpatieMediaLibraryFileUpload returns an array)
            if (is_array($imageState)) {
                $imageState = collect($imageState)->first();
            }

            // Handle temporary uploaded files (TemporaryUploadedFile objects)
            if ($imageState instanceof TemporaryUploadedFile) {

                try {
                    return $imageState->temporaryUrl();
                } catch (Throwable) {
                    // Fall back to the preview-file route if temporaryUrl() fails
                    // (e.g., when metadata file doesn't exist on S3/R2)
                    return "/livewire/preview-file/{$imageState->getFilename()}";
                }
            }

            // Handle UUID strings for Livewire temporary uploads
            if (is_string($imageState) && ! empty($imageState)) {
                // Check if it's a UUID (Livewire temporary file identifier)
                if (strlen($imageState) === 36 && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $imageState)) {
                    return "/livewire/preview-file/{$imageState}";
                }

                // Otherwise treat as a storage path
                return Storage::url($imageState);
            }

            return null;
        });
    }

    public function collection(string | Closure | null $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function image(string | Closure | null $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->evaluate($this->image);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('50% 50%');
    }
}
