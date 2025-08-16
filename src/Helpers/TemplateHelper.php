<?php

namespace FrankenCms\Helpers;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class TemplateHelper
{
    public static function getTemplates(): array
    {
        $templateFolder = config('franken-cms.template_folder');
        $templatePath = resource_path("views/{$templateFolder}");

        if (! is_dir($templatePath) || ! is_readable($templatePath)) {
            Log::warning("Template directory not found or not readable: {$templatePath}");
            return [];
        }

        try {
            $files = File::glob($templatePath . '/*.blade.php');

            if (empty($files)) {
                Log::info("No blade templates found in: {$templatePath}");
                return [];
            }

            // Map filenames to key-value pairs for select options
            return collect($files)
                ->mapWithKeys(function ($file) {
                    $baseName = str(pathinfo($file, PATHINFO_FILENAME))
                        ->beforeLast('.blade')
                        ->toString();

                    $label = str($baseName)
                        ->replace(['-', '_'], ' ')
                        ->title()
                        ->toString();

                    return [$baseName => $label];
                })
                ->filter()
                ->toArray();
        } catch (Exception $e) {
            Log::error('Error processing template files: ' . $e->getMessage());
            return [];
        }
    }
}
