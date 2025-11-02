<?php

namespace FrankenCms\Helpers;

use Exception;
use FrankenCms\Services\CmsFieldRenderer;
use FrankenCms\Services\CurrentPageService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class TemplateHelper
{
    /**
     * Get all templates, optionally filtered by prefix
     *
     * @param  string|null  $prefix  Filter templates by prefix (e.g., 'page-', 'post-')
     */
    public static function getTemplates(?string $prefix = null): array
    {
        $themeFolder = config('franken-cms.theme_folder');
        $templatePath = resource_path("views/{$themeFolder}");

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
                ->map(function ($file) {
                    return str(pathinfo($file, PATHINFO_FILENAME))
                        ->beforeLast('.blade')
                        ->toString();
                })
                ->when($prefix, function ($collection) use ($prefix) {
                    return $collection->filter(fn ($baseName) => str($baseName)->startsWith($prefix));
                })
                ->mapWithKeys(function ($baseName) {
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

    /**
     * Get page templates (prefixed with 'page-')
     */
    public static function getPageTemplates(): array
    {
        return self::getTemplates('page-');
    }

    /**
     * Get post templates (prefixed with 'post-' or exactly 'post')
     */
    public static function getPostTemplates(): array
    {
        $templates = self::getTemplates('post');

        return collect($templates)
            ->filter(fn ($label, $key) => $key === 'post' || str($key)->startsWith('post-'))
            ->toArray();
    }

    /**
     * Render a CMS field value from the current page
     *
     * @param  string  $fieldName  The field name (supports dot notation)
     * @param  string  $fieldType  The field type (text, textarea, repeater, etc.)
     * @param  array  $options  Additional options (not used for rendering, only for admin)
     * @return mixed The rendered field value
     */
    public static function cmsField(string $fieldName, string $fieldType = 'text', array $options = []): mixed
    {
        $renderer = app(CmsFieldRenderer::class);
        $currentPage = app(CurrentPageService::class)->getPage();

        // For image fields using Spatie Media Library, get from media collection
        if ($fieldType === 'image') {
            // Use the field name as the collection name
            $media = $currentPage->getFirstMedia($fieldName);

            // Wrap media with options from template
            $fieldValue = [
                'media'   => $media,
                'options' => $options,
            ];
        }
        // For all other fields, get from custom_fields JSON
        else {
            // Convert dot notation to nested array access (e.g., 'hero.title' -> custom_fields['hero']['title'])
            $customFields = $currentPage->custom_fields ?? [];
            $fieldValue = data_get($customFields, $fieldName);
        }

        // Render the field value (only images show placeholders)
        return $renderer->render($fieldType, $fieldValue, $fieldName);
    }
}
