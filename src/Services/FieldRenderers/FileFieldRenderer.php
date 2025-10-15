<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\Facades\Storage;

class FileFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        $disk = config('franken-cms.media_disk_name', 'public');

        return Storage::disk($disk)->url($value);
    }
}
