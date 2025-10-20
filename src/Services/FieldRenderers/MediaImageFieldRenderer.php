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
     * @param  mixed  $value  The field configuration array from @cmsField
     * @return HtmlString The rendered HTML
     */
    public function render(mixed $value): HtmlString
    {
        if (! is_array($value) || ! isset($value['_context'])) {
            return new HtmlString('');
        }

        $context = $value['_context'];
        $model = $context['model'] ?? null;
        $fieldName = $context['field_name'] ?? null;
        $collection = $value['collection'] ?? $fieldName;
        $format = $value['format'] ?? 'img'; // img, figure, picture
        $showCaption = $value['show_caption'] ?? true;
        $showAttribution = $value['show_attribution'] ?? false;
        $classNames = $value['class'] ?? '';

        if (! $model || ! $fieldName || ! $model instanceof HasMedia) {
            return new HtmlString('');
        }

        // Get image data with metadata
        $imageData = ImageFieldSchema::getImageData($model, $fieldName, $collection);

        if (! $imageData) {
            return new HtmlString('');
        }

        return match ($format) {
            'figure' => $this->renderAsFigure($imageData, $showCaption, $showAttribution, $classNames),
            'picture' => $this->renderAsPicture($imageData, $value, $classNames),
            default => $this->renderAsImg($imageData, $classNames),
        };
    }

    /**
     * Render as simple <img> tag
     */
    protected function renderAsImg(array $imageData, string $additionalClasses = ''): HtmlString
    {
        $classes = trim(($imageData['css'] ?? '').' '.$additionalClasses);

        $html = '<img';
        $html .= ' src="'.htmlspecialchars($imageData['url']).'"';

        if (! empty($imageData['alt'])) {
            $html .= ' alt="'.htmlspecialchars($imageData['alt']).'"';
        }

        if (! empty($imageData['title'])) {
            $html .= ' title="'.htmlspecialchars($imageData['title']).'"';
        }

        if (! empty($imageData['width'])) {
            $html .= ' width="'.htmlspecialchars($imageData['width']).'"';
        }

        if (! empty($imageData['height'])) {
            $html .= ' height="'.htmlspecialchars($imageData['height']).'"';
        }

        if (! empty($imageData['loading'])) {
            $html .= ' loading="'.htmlspecialchars($imageData['loading']).'"';
        }

        if (! empty($classes)) {
            $html .= ' class="'.htmlspecialchars($classes).'"';
        }

        // Add focal point styling
        if (isset($imageData['focal_x']) && isset($imageData['focal_y'])) {
            $focalX = $imageData['focal_x'];
            $focalY = $imageData['focal_y'];
            $html .= ' style="object-fit: cover; object-position: '
                .htmlspecialchars($focalX).'% '
                .htmlspecialchars($focalY).'%;"';
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
            $html .= ' class="'.htmlspecialchars($additionalClasses).'"';
        }
        $html .= '>';

        // Render the img tag (without additional classes, they're on figure)
        $html .= $this->renderAsImg($imageData, '');

        // Add caption if present and enabled
        if ($showCaption && (! empty($imageData['caption']) || ($showAttribution && ! empty($imageData['attribution'])))) {
            $html .= '<figcaption>';

            if (! empty($imageData['caption'])) {
                $html .= '<span class="caption-text">'.htmlspecialchars($imageData['caption']).'</span>';
            }

            if ($showAttribution && ! empty($imageData['attribution'])) {
                if (! empty($imageData['caption'])) {
                    $html .= ' ';
                }
                $html .= '<span class="caption-attribution">'.htmlspecialchars($imageData['attribution']).'</span>';
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
                $html .= ' srcset="'.htmlspecialchars($imageData['media']->getUrl($conversion)).'"';
            }

            if ($media) {
                $html .= ' media="'.htmlspecialchars($media).'"';
            }

            if ($type) {
                $html .= ' type="'.htmlspecialchars($type).'"';
            }

            $html .= '>';
        }

        // Fallback img tag
        $html .= $this->renderAsImg($imageData, $additionalClasses);

        $html .= '</picture>';

        return new HtmlString($html);
    }
}
