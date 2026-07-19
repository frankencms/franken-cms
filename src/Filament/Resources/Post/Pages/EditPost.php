<?php

namespace FrankenCms\Filament\Resources\Post\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Post\PostResource;
use FrankenCms\Helpers\PostHelper;
use FrankenCms\Models\Post;
use FrankenCms\Services\TemplateFieldExtractor;
use FrankenCms\Support\FocalPoint;
use Spatie\MediaLibrary\Conversions\FileManipulator;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected array $featuredImageMetadata = [];

    protected array $customImageFieldsMetadata = [];

    protected function getListeners(): array
    {
        return [
            'ai-content-generated'     => 'handleAiContentGenerated',
            'insert-generated-content' => 'handleInsertGeneratedContent',
        ];
    }

    public function handleAiContentGenerated(array $data): void
    {
        if (isset($data['fieldName']) && isset($data['value'])) {
            $this->data[$data['fieldName']] = $data['value'];
        }
    }

    public function handleInsertGeneratedContent($content = '', $componentId = ''): void
    {
        if ($content) {
            // Set the HTML content directly - Filament's RichEditor will handle it
            $this->data['post_content'] = $content;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load custom image fields metadata
        $data = $this->loadCustomImageFieldsMetadata($data);

        return $data;
    }

    protected function loadCustomImageFieldsMetadata(array $data): array
    {
        // Get the template name from the record
        $template = $this->record->template ?? null;

        if (! $template) {
            return $data;
        }

        // Parse template to find media_image fields
        $themeFolder = config('franken-cms.theme_folder');
        $templatePath = resource_path("views/{$themeFolder}/{$template}.blade.php");

        if (! file_exists($templatePath)) {
            return $data;
        }

        $extractor = app(TemplateFieldExtractor::class);
        $fields = $extractor->parseTemplate($templatePath);

        // Load metadata for each image field (media_image is legacy alias)
        foreach ($fields as $identifier => $field) {
            if (! in_array($field['type'], ['image', 'media_image'])) {
                continue;
            }

            $collection = $field['properties']['collection'] ?? $identifier;

            if (! $this->record->hasMedia($collection)) {
                continue;
            }

            $media = $this->record->getFirstMedia($collection);

            if (! $media) {
                continue;
            }

            // Load metadata from media custom properties
            $metadata = $media->getCustomProperty("{$identifier}_data", []);

            if (! empty($metadata)) {
                $data["{$identifier}_alt"] = $metadata['alt'] ?? '';
                $data["{$identifier}_title"] = $metadata['title'] ?? '';
                $data["{$identifier}_caption"] = $metadata['caption'] ?? '';
                $data["{$identifier}_attribution"] = $metadata['attribution'] ?? '';
                $data["{$identifier}_css"] = $metadata['css'] ?? '';
                $data["{$identifier}_lazy_loading"] = ($metadata['loading'] ?? 'lazy') === 'lazy';
                $data["{$identifier}_fetchpriority"] = $metadata['fetchpriority'] ?? 'none';
                $data["{$identifier}_width"] = $metadata['width'] ?? null;
                $data["{$identifier}_height"] = $metadata['height'] ?? null;
                $data["{$identifier}_focal_point"] = FocalPoint::toPercentString(
                    FocalPoint::normalize($metadata['focal_point'] ?? null)
                );
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract featured image metadata before mass assignment
        $this->featuredImageMetadata = [
            'alt'           => $data['featured_image_alt'] ?? '',
            'title'         => $data['featured_image_title'] ?? '',
            'caption'       => $data['featured_image_caption'] ?? '',
            'attribution'   => $data['featured_image_attribution'] ?? '',
            'css_classes'   => $data['featured_image_css'] ?? '',
            'lazy_loading'  => $data['featured_image_lazy_loading'] ?? false,
            'fetchpriority' => $data['featured_image_fetchpriority'] ?? 'none',
            'width'         => $data['featured_image_width'] ?? null,
            'height'        => $data['featured_image_height'] ?? null,
            'focal_point'   => $data['featured_image_focal_point'] ?? '50% 50%',
        ];

        // Remove featured image metadata from data array to prevent mass assignment errors
        unset(
            $data['featured_image_alt'],
            $data['featured_image_title'],
            $data['featured_image_caption'],
            $data['featured_image_attribution'],
            $data['featured_image_css'],
            $data['featured_image_lazy_loading'],
            $data['featured_image_fetchpriority'],
            $data['featured_image_width'],
            $data['featured_image_height'],
            $data['featured_image_focal_point']
        );

        // Extract custom image fields metadata
        $data = $this->extractCustomImageFieldsMetadata($data);

        return $data;
    }

    protected function extractCustomImageFieldsMetadata(array $data): array
    {
        // Get the template name from the record
        $template = $this->record->template ?? null;

        if (! $template) {
            return $data;
        }

        // Parse template to find media_image fields
        $themeFolder = config('franken-cms.theme_folder');
        $templatePath = resource_path("views/{$themeFolder}/{$template}.blade.php");

        if (! file_exists($templatePath)) {
            return $data;
        }

        $extractor = app(TemplateFieldExtractor::class);
        $fields = $extractor->parseTemplate($templatePath);

        // Extract metadata for each image field (media_image is legacy alias)
        foreach ($fields as $identifier => $field) {
            if (! in_array($field['type'], ['image', 'media_image'])) {
                continue;
            }

            // Extract metadata for this field
            $this->customImageFieldsMetadata[$identifier] = [
                'alt'           => $data["{$identifier}_alt"] ?? '',
                'title'         => $data["{$identifier}_title"] ?? '',
                'caption'       => $data["{$identifier}_caption"] ?? '',
                'attribution'   => $data["{$identifier}_attribution"] ?? '',
                'css'           => $data["{$identifier}_css"] ?? '',
                'lazy_loading'  => $data["{$identifier}_lazy_loading"] ?? true,
                'fetchpriority' => $data["{$identifier}_fetchpriority"] ?? 'none',
                'width'         => $data["{$identifier}_width"] ?? null,
                'height'        => $data["{$identifier}_height"] ?? null,
                'focal_point'   => $data["{$identifier}_focal_point"] ?? '50% 50%',
                'collection'    => $field['properties']['collection'] ?? $identifier,
            ];

            // Remove from data array to prevent mass assignment errors
            unset(
                $data["{$identifier}_alt"],
                $data["{$identifier}_title"],
                $data["{$identifier}_caption"],
                $data["{$identifier}_attribution"],
                $data["{$identifier}_css"],
                $data["{$identifier}_lazy_loading"],
                $data["{$identifier}_fetchpriority"],
                $data["{$identifier}_width"],
                $data["{$identifier}_height"],
                $data["{$identifier}_focal_point"]
            );
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->saveFeaturedImageMetadata();
        $this->saveCustomImageFieldsMetadata();
        $this->saveSeoTwitterToggle();
        $this->calculateAndSaveReadTime();
    }

    protected function calculateAndSaveReadTime(): void
    {
        /** @var Post $record */
        $record = $this->record;

        // Only calculate read time for posts with content
        if (empty($record->post_content)) {
            return;
        }

        $readTime = PostHelper::calculate_read_time(
            PostHelper::convert_tip_tap_to_plain_text($record->post_content)
        );

        $record->setMeta('read_time', $readTime);
    }

    protected function saveSeoTwitterToggle(): void
    {
        /** @var Post $record */
        $record = $this->record;

        // Explicitly save SEO Twitter summary toggle to postmeta
        // This ensures the value is saved even if the user doesn't interact with the toggle
        $formData = $this->data;
        if (isset($formData['seo_use_twitter_summary'])) {
            $record->setMeta('seo_use_twitter_summary', (bool) $formData['seo_use_twitter_summary']);
        }
    }

    protected function saveFeaturedImageMetadata(): void
    {
        /** @var Post $record */
        $record = $this->record;

        if (! $record->hasMedia('featured') || ! isset($this->featuredImageMetadata)) {
            return;
        }

        $media = $record->getFirstMedia('featured');

        // Get the existing focal point to check if it changed (normalize —
        // legacy data may hold the array form)
        $existingFocalPoint = FocalPoint::normalize($media->getCustomProperty('focal_point'));
        $newFocalPoint = FocalPoint::normalize($this->featuredImageMetadata['focal_point'] ?? null);

        // Save custom properties directly to the media item
        $media->setCustomProperty('alt', $this->featuredImageMetadata['alt']);
        $media->setCustomProperty('title', $this->featuredImageMetadata['title']);
        $media->setCustomProperty('caption', $this->featuredImageMetadata['caption']);
        $media->setCustomProperty('attribution', $this->featuredImageMetadata['attribution']);
        $media->setCustomProperty('css_classes', $this->featuredImageMetadata['css_classes']);
        $media->setCustomProperty('lazy_loading', $this->featuredImageMetadata['lazy_loading']);
        $media->setCustomProperty('fetchpriority', $this->featuredImageMetadata['fetchpriority']);
        $media->setCustomProperty('width', $this->featuredImageMetadata['width']);
        $media->setCustomProperty('height', $this->featuredImageMetadata['height']);
        $media->setCustomProperty('focal_point', FocalPoint::toPercentString($newFocalPoint));

        $media->save();

        // Regenerate featured image conversions if focal point changed
        // Only regenerate thumb, featured, and listing - NOT og/twitter (SEO images don't use focal points)
        if ($existingFocalPoint !== $newFocalPoint) {
            app(FileManipulator::class)->createDerivedFiles($media, ['thumb', 'featured', 'listing']);
        }
    }

    protected function saveCustomImageFieldsMetadata(): void
    {
        /** @var Post $record */
        $record = $this->record;

        if (empty($this->customImageFieldsMetadata)) {
            return;
        }

        // Save metadata for each custom image field
        foreach ($this->customImageFieldsMetadata as $fieldName => $metadata) {
            $collection = $metadata['collection'];

            if (! $record->hasMedia($collection)) {
                continue;
            }

            $media = $record->getFirstMedia($collection);

            if (! $media) {
                continue;
            }

            // Save all metadata to the media item
            $media->setCustomProperty("{$fieldName}_data", [
                'alt'           => $metadata['alt'],
                'title'         => $metadata['title'],
                'caption'       => $metadata['caption'],
                'attribution'   => $metadata['attribution'],
                'css'           => $metadata['css'],
                'loading'       => $metadata['lazy_loading'] ? 'lazy' : 'eager',
                'fetchpriority' => $metadata['fetchpriority'],
                'width'         => $metadata['width'],
                'height'        => $metadata['height'],
                'focal_point'   => $metadata['focal_point'],
            ]);

            $media->save();
        }
    }
}
