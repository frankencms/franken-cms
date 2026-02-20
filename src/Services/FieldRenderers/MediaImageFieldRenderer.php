<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use FrankenCms\Filament\Schemas\ImageFieldSchema;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\HasMedia;

class MediaImageFieldRenderer implements FieldRendererInterface
{
    /**
     * Render the media image field for display in templates
     *
     * @param  mixed  $value  The field configuration array from @frankenMediaImage
     * @param  string|null  $fieldName  The field name (for placeholder generation)
     * @return HtmlString The rendered HTML
     */
    public function render(mixed $value, ?string $fieldName = null): HtmlString
    {
        if (! is_array($value) || ! isset($value['_context'])) {
            return new HtmlString('');
        }

        $context = $value['_context'];
        $model = $context['model'] ?? null;
        $fieldNameFromContext = $context['field_name'] ?? null;
        $showPlaceholder = $context['show_placeholder'] ?? true;
        $collection = $value['collection'] ?? $fieldNameFromContext;
        $format = $value['format'] ?? 'img'; // img, figure, picture
        $showCaption = $value['show_caption'] ?? true;
        $showAttribution = $value['show_attribution'] ?? false;
        $classNames = $value['class'] ?? '';

        if (! $model || ! $fieldNameFromContext || ! $model instanceof HasMedia) {
            return new HtmlString('');
        }

        // Get image data with metadata
        $imageData = ImageFieldSchema::getImageData($model, $fieldNameFromContext, $collection);

        if (! $imageData) {
            // Return placeholder if enabled
            if ($fieldNameFromContext && $showPlaceholder) {
                $fieldLabel = str($fieldNameFromContext)->replace(['.', '_'], ' ')->title();
                return new HtmlString(
                    '<div style="display: inline-block; padding: 2rem; background: rgba(59, 130, 246, 0.05); border: 2px dashed rgba(59, 130, 246, 0.2); border-radius: 0.5rem; text-align: center; min-width: 300px;">' .
                    '<svg style="display: inline-block; width: 4rem; height: 4rem; color: rgba(59, 130, 246, 0.3); margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>' .
                    '<div style="color: rgba(59, 130, 246, 0.6); font-size: 0.875rem;">' . htmlspecialchars($fieldLabel) . '</div>' .
                    '</div>'
                );
            }
            return new HtmlString('');
        }

        return match ($format) {
            'figure'  => $this->renderAsFigure($imageData, $showCaption, $showAttribution, $classNames),
            'picture' => $this->renderAsPicture($imageData, $value, $classNames),
            default   => $this->renderAsImg($imageData, $classNames),
        };
    }

    /**
     * Render as simple <img> tag
     */
    protected function renderAsImg(array $imageData, string $additionalClasses = ''): HtmlString
    {
        $classes = trim(($imageData['css'] ?? '') . ' ' . $additionalClasses);

        $html = '<img';
        $html .= ' src="' . htmlspecialchars($imageData['url']) . '"';

        if (! empty($imageData['alt'])) {
            $html .= ' alt="' . htmlspecialchars($imageData['alt']) . '"';
        }

        if (! empty($imageData['title'])) {
            $html .= ' title="' . htmlspecialchars($imageData['title']) . '"';
        }

        if (! empty($imageData['width'])) {
            $html .= ' width="' . htmlspecialchars($imageData['width']) . '"';
        }

        if (! empty($imageData['height'])) {
            $html .= ' height="' . htmlspecialchars($imageData['height']) . '"';
        }

        if (! empty($imageData['loading'])) {
            $html .= ' loading="' . htmlspecialchars($imageData['loading']) . '"';
        }

        if (! empty($imageData['fetchpriority']) && $imageData['fetchpriority'] !== 'none') {
            $html .= ' fetchpriority="' . htmlspecialchars($imageData['fetchpriority']) . '"';
        }

        if (! empty($classes)) {
            $html .= ' class="' . htmlspecialchars($classes) . '"';
        }

        // Add focal point styling (skip if centered at 50% 50% as that's the default)
        if (isset($imageData['focal_point']) && ! empty($imageData['focal_point'])) {
            $focalPoint = $imageData['focal_point'];

            // Only add focal point styling if it's NOT centered (50% 50%)
            if ($focalPoint !== '50% 50%') {
                $html .= ' style="object-fit: cover; object-position: '
                    . htmlspecialchars($focalPoint) . ';"';
            }
        }

        $html .= '>';

        return new HtmlString($html);
    }

    /**
     * Render as <figure> with optional <figcaption>
     */
    protected function renderAsFigure(
        array $imageData,
        bool $showCaption,
        bool $showAttribution,
        string $additionalClasses = ''
    ): HtmlString {
        $html = '<figure';
        if (! empty($additionalClasses)) {
            $html .= ' class="' . htmlspecialchars($additionalClasses) . '"';
        }
        $html .= '>';

        // Render the img tag (without additional classes, they're on figure)
        $html .= $this->renderAsImg($imageData, '');

        // Add caption if present and enabled
        if ($showCaption && (! empty($imageData['caption']) || ($showAttribution && ! empty($imageData['attribution'])))) {
            $html .= '<figcaption>';

            if (! empty($imageData['caption'])) {
                $html .= '<span class="caption-text">' . htmlspecialchars($imageData['caption']) . '</span>';
            }

            if ($showAttribution && ! empty($imageData['attribution'])) {
                if (! empty($imageData['caption'])) {
                    $html .= ' ';
                }
                $html .= '<span class="caption-attribution">' . htmlspecialchars($imageData['attribution']) . '</span>';
            }

            $html .= '</figcaption>';
        }

        $html .= '</figure>';

        return new HtmlString($html);
    }

    /**
     * Render as <picture> with responsive sources
     */
    protected function renderAsPicture(array $imageData, array $config, string $additionalClasses = ''): HtmlString
    {
        $media = $imageData['media'];
        $sources = $config['sources'] ?? [];

        $html = '<picture>';

        // Add source elements for different breakpoints/formats
        foreach ($sources as $source) {
            $conversion = $source['conversion'] ?? null;
            $media = $source['media'] ?? null;
            $type = $source['type'] ?? null;

            $html .= '<source';

            if ($conversion && method_exists($imageData['media'], 'getUrl')) {
                $html .= ' srcset="' . htmlspecialchars($imageData['media']->getUrl($conversion)) . '"';
            }

            if ($media) {
                $html .= ' media="' . htmlspecialchars($media) . '"';
            }

            if ($type) {
                $html .= ' type="' . htmlspecialchars($type) . '"';
            }

            $html .= '>';
        }

        // Fallback img tag
        $html .= $this->renderAsImg($imageData, $additionalClasses);

        $html .= '</picture>';

        return new HtmlString($html);
    }
}
