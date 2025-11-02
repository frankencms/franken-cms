<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SpatieImageFieldRenderer implements FieldRendererInterface
{
    /**
     * Render image from Spatie Media Library
     *
     * @param  mixed  $value  The media UUID, Media instance, or array with media data and options
     * @param  string|null  $fieldName  The field name (for placeholder generation)
     * @return HtmlString The rendered HTML
     */
    public function render(mixed $value, ?string $fieldName = null): HtmlString
    {
        $media = null;
        $templateOptions = [];

        // Handle wrapped array with media and options
        if (is_array($value) && isset($value['media'])) {
            $media = $value['media'];
            $templateOptions = $value['options'] ?? [];
        }
        // Handle UUID string
        elseif (is_string($value)) {
            $media = Media::where('uuid', $value)->first();
        }
        // Handle Media instance
        elseif ($value instanceof Media) {
            $media = $value;
        }

        // If no media, show placeholder
        if (! $media) {
            return $this->renderPlaceholder($fieldName);
        }

        return $this->renderImage($media, $templateOptions);
    }

    /**
     * Render the image tag with all metadata
     *
     * @param  Media  $media  The media model
     * @param  array  $templateOptions  Options passed from the template (e.g., 'class', 'width', 'height')
     */
    protected function renderImage(Media $media, array $templateOptions = []): HtmlString
    {
        // Get the field name from the collection name and normalize it (dots to underscores)
        $collectionName = $media->collection_name;
        $normalizedFieldName = str_replace('.', '_', $collectionName);

        // Get metadata from the {normalizedFieldName}_data custom property (ImageFieldSchema format)
        $metadata = $media->getCustomProperty("{$normalizedFieldName}_data", []);

        $html = '<img';

        // Source URL
        $html .= ' src="' . htmlspecialchars($media->getUrl()) . '"';

        // Alt text: template option > metadata
        $alt = $templateOptions['alt'] ?? ($metadata['alt'] ?? '');
        if (! empty($alt)) {
            $html .= ' alt="' . htmlspecialchars($alt) . '"';
        } else {
            $html .= ' alt=""';
        }

        // Title: template option > metadata
        $title = $templateOptions['title'] ?? ($metadata['title'] ?? '');
        if (! empty($title)) {
            $html .= ' title="' . htmlspecialchars($title) . '"';
        }

        // Dimensions: template option > metadata
        $width = $templateOptions['width'] ?? ($metadata['width'] ?? null);
        $height = $templateOptions['height'] ?? ($metadata['height'] ?? null);

        if ($width) {
            $html .= ' width="' . htmlspecialchars($width) . '"';
        }

        if ($height) {
            $html .= ' height="' . htmlspecialchars($height) . '"';
        }

        // Loading strategy: template option > metadata
        $loading = $templateOptions['loading'] ?? ($metadata['loading'] ?? 'lazy');
        $html .= ' loading="' . htmlspecialchars($loading) . '"';

        // CSS classes: merge template option with metadata
        $templateClass = $templateOptions['class'] ?? '';
        $mediaClass = $metadata['css'] ?? '';
        $combinedClasses = trim($mediaClass . ' ' . $templateClass);

        if (! empty($combinedClasses)) {
            $html .= ' class="' . htmlspecialchars($combinedClasses) . '"';
        }

        // Build inline styles
        $styles = [];

        // Focal point (skip if centered at 50%, 50%)
        $focalX = (float) ($metadata['focal_x'] ?? 50);
        $focalY = (float) ($metadata['focal_y'] ?? 50);

        if ($focalX !== 50.0 || $focalY !== 50.0) {
            $styles[] = 'object-fit: cover';
            $styles[] = 'object-position: ' . htmlspecialchars($focalX) . '% ' . htmlspecialchars($focalY) . '%';
        }

        // Add any custom style from template
        if (isset($templateOptions['style'])) {
            $styles[] = $templateOptions['style'];
        }

        if (! empty($styles)) {
            $html .= ' style="' . implode('; ', $styles) . ';"';
        }

        $html .= '>';

        return new HtmlString($html);
    }

    /**
     * Render a placeholder when no image is available
     */
    protected function renderPlaceholder(?string $fieldName): HtmlString
    {
        if (! $fieldName) {
            return new HtmlString('');
        }

        $fieldLabel = str($fieldName)->replace(['.', '_'], ' ')->title();

        return new HtmlString(
            '<div style="display: inline-block; padding: 2rem; background: rgba(59, 130, 246, 0.05); border: 2px dashed rgba(59, 130, 246, 0.2); border-radius: 0.5rem; text-align: center; min-width: 300px;">' .
            '<svg style="display: inline-block; width: 4rem; height: 4rem; color: rgba(59, 130, 246, 0.3); margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>' .
            '<div style="color: rgba(59, 130, 246, 0.6); font-size: 0.875rem;">' . htmlspecialchars($fieldLabel) . '</div>' .
            '</div>'
        );
    }
}
