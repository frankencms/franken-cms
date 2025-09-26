<?php

namespace FrankenCms\Filament\Resources\Post\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Post\PostResource;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Save custom properties to the featured image media item
        if ($this->record->hasMedia('featured')) {
            $media = $this->record->getFirstMedia('featured');

            // Update custom properties on the media item
            $media->setCustomProperty('alt', $data['featured_image_alt'] ?? '');
            $media->setCustomProperty('title', $data['featured_image_title'] ?? '');
            $media->setCustomProperty('caption', $data['featured_image_caption'] ?? '');
            $media->setCustomProperty('attribution', $data['featured_image_attribution'] ?? '');
            $media->setCustomProperty('css_classes', $data['featured_image_css'] ?? '');
            $media->setCustomProperty('lazy_loading', $data['featured_image_lazy_loading'] ?? false);
            $media->setCustomProperty('width', $data['featured_image_width'] ?? null);
            $media->setCustomProperty('height', $data['featured_image_height'] ?? null);
            $media->setCustomProperty('focal_point', [
                'x' => $data['featured_image_focal_x'] ?? 50,
                'y' => $data['featured_image_focal_y'] ?? 50,
            ]);

            $media->save();
        }

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
}
