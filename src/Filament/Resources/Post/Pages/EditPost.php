<?php

namespace FrankenCms\Filament\Resources\Post\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Post\PostResource;
use FrankenCms\Models\Post;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected array $featuredImageMetadata = [];

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract featured image metadata before mass assignment
        $this->featuredImageMetadata = [
            'alt'          => $data['featured_image_alt'] ?? '',
            'title'        => $data['featured_image_title'] ?? '',
            'caption'      => $data['featured_image_caption'] ?? '',
            'attribution'  => $data['featured_image_attribution'] ?? '',
            'css_classes'  => $data['featured_image_css'] ?? '',
            'lazy_loading' => $data['featured_image_lazy_loading'] ?? false,
            'width'        => $data['featured_image_width'] ?? null,
            'height'       => $data['featured_image_height'] ?? null,
            'focal_point'  => [
                'x' => $data['featured_image_focal_x'] ?? 50,
                'y' => $data['featured_image_focal_y'] ?? 50,
            ],
        ];

        // Remove featured image metadata from data array to prevent mass assignment errors
        unset(
            $data['featured_image_alt'],
            $data['featured_image_title'],
            $data['featured_image_caption'],
            $data['featured_image_attribution'],
            $data['featured_image_css'],
            $data['featured_image_lazy_loading'],
            $data['featured_image_width'],
            $data['featured_image_height'],
            $data['featured_image_focal_x'],
            $data['featured_image_focal_y']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $this->saveFeaturedImageMetadata();
        $this->saveSeoTwitterToggle();
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

        // Get the existing focal point to check if it changed
        $existingFocalPoint = $media->getCustomProperty('focal_point', ['x' => 50, 'y' => 50]);
        $newFocalPoint = $this->featuredImageMetadata['focal_point'];

        // Save custom properties directly to the media item
        $media->setCustomProperty('alt', $this->featuredImageMetadata['alt']);
        $media->setCustomProperty('title', $this->featuredImageMetadata['title']);
        $media->setCustomProperty('caption', $this->featuredImageMetadata['caption']);
        $media->setCustomProperty('attribution', $this->featuredImageMetadata['attribution']);
        $media->setCustomProperty('css_classes', $this->featuredImageMetadata['css_classes']);
        $media->setCustomProperty('lazy_loading', $this->featuredImageMetadata['lazy_loading']);
        $media->setCustomProperty('width', $this->featuredImageMetadata['width']);
        $media->setCustomProperty('height', $this->featuredImageMetadata['height']);
        $media->setCustomProperty('focal_point', $newFocalPoint);

        $media->save();

        // Regenerate featured image conversions if focal point changed
        // Only regenerate thumb, featured, and listing - NOT og/twitter (SEO images don't use focal points)
        if ($existingFocalPoint['x'] !== $newFocalPoint['x'] || $existingFocalPoint['y'] !== $newFocalPoint['y']) {
            app(\Spatie\MediaLibrary\Conversions\FileManipulator::class)->createDerivedFiles($media, ['thumb', 'featured', 'listing']);
        }
    }
}
