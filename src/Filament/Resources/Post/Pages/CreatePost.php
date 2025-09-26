<?php

namespace FrankenCms\Filament\Resources\Post\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Post\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove featured image fields from data to prevent mass assignment error
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
        // Save custom properties to the featured image media item
        $record = $this->record;

        if ($record->hasMedia('featured')) {
            $media = $record->getFirstMedia('featured');
            $formData = $this->form->getState();

            // Update custom properties on the media item
            $media->setCustomProperty('alt', $formData['featured_image_alt'] ?? '');
            $media->setCustomProperty('title', $formData['featured_image_title'] ?? '');
            $media->setCustomProperty('caption', $formData['featured_image_caption'] ?? '');
            $media->setCustomProperty('attribution', $formData['featured_image_attribution'] ?? '');
            $media->setCustomProperty('css_classes', $formData['featured_image_css'] ?? '');
            $media->setCustomProperty('lazy_loading', $formData['featured_image_lazy_loading'] ?? false);
            $media->setCustomProperty('width', $formData['featured_image_width'] ?? null);
            $media->setCustomProperty('height', $formData['featured_image_height'] ?? null);
            $media->setCustomProperty('focal_point', [
                'x' => $formData['featured_image_focal_x'] ?? 50,
                'y' => $formData['featured_image_focal_y'] ?? 50,
            ]);

            $media->save();
        }
    }
}
