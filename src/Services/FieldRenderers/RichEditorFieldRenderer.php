<?php

namespace FrankenCms\Services\FieldRenderers;

use Exception;
use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\HtmlString;
use Tiptap\Editor;

class RichEditorFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value, ?string $fieldName = null): HtmlString
    {
        if (empty($value)) {
            // Return placeholder for empty content
            if ($fieldName) {
                $fieldLabel = str($fieldName)->replace(['.', '_'], ' ')->title();
                return new HtmlString(
                    '<div style="display: block; padding: 2rem; background: rgba(59, 130, 246, 0.05); border: 2px dashed rgba(59, 130, 246, 0.2); border-radius: 0.5rem; text-align: center; min-width: 300px;">' .
                    '<svg style="display: inline-block; width: 4rem; height: 4rem; color: rgba(59, 130, 246, 0.3); margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>' .
                    '<div style="color: rgba(59, 130, 246, 0.6); font-size: 0.875rem;">' . htmlspecialchars($fieldLabel) . '</div>' .
                    '</div>'
                );
            }
            return new HtmlString('');
        }

        // If value is a string and already HTML (not JSON), return it as-is
        if (is_string($value) && ! str_starts_with(trim($value), '{') && ! str_starts_with(trim($value), '[')) {
            return new HtmlString($value);
        }

        try {
            // Convert TipTap JSON to HTML
            // Value might be a JSON string or already decoded array
            $editor = new Editor([
                'content'    => $value,
                'extensions' => [
                    new \Tiptap\Extensions\StarterKit,
                    new \Tiptap\Nodes\Image,
                ],
            ]);

            $html = $editor->getHTML();

            // Post-process HTML to add enhanced image attributes
            $html = $this->processEnhancedImages($html, $value);

            return new HtmlString($html);
        } catch (Exception $e) {
            // If conversion fails, return the raw value as string
            return new HtmlString(is_string($value) ? $value : '');
        }
    }

    /**
     * Process enhanced images to add custom attributes
     */
    public function processEnhancedImages(string $html, string | array $json): string
    {
        // Parse the JSON to find image nodes
        $content = is_array($json) ? $json : json_decode($json, true);
        if (! $content || ! isset($content['content'])) {
            return $html;
        }

        // Find all image nodes with their attributes
        $images = $this->findImageNodes($content);

        if (empty($images)) {
            return $html;
        }

        // Replace each <img> tag with enhanced version
        // We need to replace them in order, so use a callback
        $imageIndex = 0;
        $html = preg_replace_callback('/<img[^>]*>/', function ($matches) use ($images, &$imageIndex) {
            if (! isset($images[$imageIndex])) {
                return $matches[0];
            }

            $attrs = $images[$imageIndex]['attrs'] ?? [];
            $imageIndex++;

            if (empty($attrs['src'])) {
                return $matches[0];
            }

            // Build the enhanced img tag
            $imgTag = '<img';

            if (! empty($attrs['src'])) {
                $imgTag .= ' src="' . htmlspecialchars($attrs['src']) . '"';
            }
            if (! empty($attrs['alt'])) {
                $imgTag .= ' alt="' . htmlspecialchars($attrs['alt']) . '"';
            }
            if (! empty($attrs['title'])) {
                $imgTag .= ' title="' . htmlspecialchars($attrs['title']) . '"';
            }
            if (! empty($attrs['width'])) {
                $imgTag .= ' width="' . htmlspecialchars($attrs['width']) . '"';
            }
            if (! empty($attrs['height'])) {
                $imgTag .= ' height="' . htmlspecialchars($attrs['height']) . '"';
            }
            if (! empty($attrs['loading'])) {
                $imgTag .= ' loading="' . htmlspecialchars($attrs['loading']) . '"';
            }
            if (! empty($attrs['css'])) {
                $imgTag .= ' class="' . htmlspecialchars($attrs['css']) . '"';
            }

            // Add focal point styling if provided
            if (isset($attrs['focal_x']) && isset($attrs['focal_y'])) {
                $focalX = $attrs['focal_x'];
                $focalY = $attrs['focal_y'];
                $imgTag .= ' style="object-fit: cover; object-position: ' . htmlspecialchars($focalX) . '% ' . htmlspecialchars($focalY) . '%;"';
            }

            $imgTag .= '>';

            return $imgTag;
        }, $html);

        return $html;
    }

    /**
     * Recursively find all image nodes in the content
     */
    protected function findImageNodes(array $node): array
    {
        $images = [];

        if (isset($node['type']) && $node['type'] === 'image') {
            $images[] = $node;
        }

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                $images = array_merge($images, $this->findImageNodes($child));
            }
        }

        return $images;
    }
}
