<?php

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class MediaSettings extends Settings
{
    // Thumbnail settings
    public int $thumbnail_width = 150;
    public int $thumbnail_height = 150;
    public bool $thumbnail_crop = false;

    // Medium size settings
    public int $medium_width = 300;
    public int $medium_height = 300;

    // Large size settings
    public int $large_width = 1024;
    public int $large_height = 1024;

    public static function group(): string
    {
        return 'cms_media';
    }
}