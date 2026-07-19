<?php

namespace FrankenCms\Helpers;

use FrankenCms\Services\AiFeatureDetector;

class MiscHelpers
{
    public static function is_ai_installed(): bool
    {
        return AiFeatureDetector::isInstalled();
    }

    public static function file_attachment_disk_name(): string
    {
        return config('franken-cms.file_attachment_disk', 'public');
    }

    public static function file_attachment_directory(): string
    {
        return config('franken-cms.file_attachment_directory', 'attachments');
    }
}
