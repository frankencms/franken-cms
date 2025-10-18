<?php

namespace FrankenCms\Filament\Resources\Post\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Post\PostResource;
use FrankenCms\Models\Post;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected array $featuredImageMetadata = [];

    protected function getListeners(): array
    {
        return [
            'ai-content-generated' => 'handleAiContentGenerated',
        ];
    }

    public function handleAiContentGenerated(array $data): void
    {
        if (isset($data['fieldName']) && isset($data['value'])) {
            $this->data[$data['fieldName']] = $data['value'];
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
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

        // Get the existing focal point to check if it's different from default
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

        // Regenerate featured image conversions if focal point is not default (50, 50)
        // The conversions are generated before this method runs, so if user set a custom focal point, regenerate
        // Only regenerate thumb, featured, and listing - NOT og/twitter (SEO images don't use focal points)
        if ($newFocalPoint['x'] != 50 || $newFocalPoint['y'] != 50) {
            app(\Spatie\MediaLibrary\Conversions\FileManipulator::class)->createDerivedFiles($media, ['thumb', 'featured', 'listing']);
        }
    }
}
