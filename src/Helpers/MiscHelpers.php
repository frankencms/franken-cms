<?php

namespace FrankenCms\Helpers;

class MiscHelpers
{
    public static function is_prism_installed(): bool
    {
        // check if the prism-php/prism composer package is installed.
        return class_exists('Prism\Prism');

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
