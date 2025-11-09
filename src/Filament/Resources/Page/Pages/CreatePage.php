<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Page\PageResource;
use FrankenCms\Models\Page;
use FrankenCms\Services\TemplateFieldExtractor;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected array $customImageFieldsMetadata = [];

    // The HasMeta trait automatically handles meta fields like 'template'
    // via the setAttribute() override and bootHasMeta() event
    // No need for manual hooks here

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
        // Default pages to published status
        // Pages are only accessible when added to menus or linked in templates
        $data['post_status'] = $data['post_status'] ?? 'published';

        // Extract custom image fields metadata
        $data = $this->extractCustomImageFieldsMetadata($data);

        return $data;
    }

    protected function extractCustomImageFieldsMetadata(array $data): array
    {
        // Get the template name from the data
        $template = $data['template'] ?? null;

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
        foreach ($fields as $field) {
            $identifier = $field['name'];

            if (! in_array($field['type'], ['image', 'media_image'])) {
                continue;
            }

            // Normalize field name (replace dots with underscores) - ImageFieldSchema does this
            $normalizedFieldName = str_replace('.', '_', $identifier);

            // Extract metadata for this field
            $this->customImageFieldsMetadata[$normalizedFieldName] = [
                'alt'          => $data["{$normalizedFieldName}_alt"] ?? '',
                'title'        => $data["{$normalizedFieldName}_title"] ?? '',
                'caption'      => $data["{$normalizedFieldName}_caption"] ?? '',
                'attribution'  => $data["{$normalizedFieldName}_attribution"] ?? '',
                'css'          => $data["{$normalizedFieldName}_css"] ?? '',
                'lazy_loading' => $data["{$normalizedFieldName}_lazy_loading"] ?? true,
                'width'        => $data["{$normalizedFieldName}_width"] ?? null,
                'height'       => $data["{$normalizedFieldName}_height"] ?? null,
                'focal_point'  => $data["{$normalizedFieldName}_focal_point"] ?? '50% 50%',
                'collection'   => $field['properties']['collection'] ?? $identifier,
            ];

            // Remove from data array to prevent mass assignment errors
            unset(
                $data["{$normalizedFieldName}_alt"],
                $data["{$normalizedFieldName}_title"],
                $data["{$normalizedFieldName}_caption"],
                $data["{$normalizedFieldName}_attribution"],
                $data["{$normalizedFieldName}_css"],
                $data["{$normalizedFieldName}_lazy_loading"],
                $data["{$normalizedFieldName}_width"],
                $data["{$normalizedFieldName}_height"],
                $data["{$normalizedFieldName}_focal_point"]
            );
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveCustomImageFieldsMetadata();
    }

    protected function saveCustomImageFieldsMetadata(): void
    {
        /** @var Page $record */
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
                'alt'         => $metadata['alt'],
                'title'       => $metadata['title'],
                'caption'     => $metadata['caption'],
                'attribution' => $metadata['attribution'],
                'css'         => $metadata['css'],
                'loading'     => $metadata['lazy_loading'] ? 'lazy' : 'eager',
                'width'       => $metadata['width'],
                'height'      => $metadata['height'],
                'focal_point' => $metadata['focal_point'],
            ]);

            $media->save();
        }
    }
}
