<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Page\PageResource;
use FrankenCms\Models\Page;
use FrankenCms\Registries\FieldRegistry;
use FrankenCms\Services\CmsFieldParser;

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

        $parser = new CmsFieldParser;
        $parser->parse($templatePath);

        $fields = FieldRegistry::getFields();

        // Extract metadata for each media_image field
        foreach ($fields as $identifier => $field) {
            if ($field['type'] !== 'media_image') {
                continue;
            }

            // Extract metadata for this field
            $this->customImageFieldsMetadata[$identifier] = [
                'alt'          => $data["{$identifier}_alt"] ?? '',
                'title'        => $data["{$identifier}_title"] ?? '',
                'caption'      => $data["{$identifier}_caption"] ?? '',
                'attribution'  => $data["{$identifier}_attribution"] ?? '',
                'css'          => $data["{$identifier}_css"] ?? '',
                'lazy_loading' => $data["{$identifier}_lazy_loading"] ?? true,
                'width'        => $data["{$identifier}_width"] ?? null,
                'height'       => $data["{$identifier}_height"] ?? null,
                'focal_x'      => $data["{$identifier}_focal_x"] ?? 50,
                'focal_y'      => $data["{$identifier}_focal_y"] ?? 50,
                'collection'   => $field['properties']['collection'] ?? $identifier,
            ];

            // Remove from data array to prevent mass assignment errors
            unset(
                $data["{$identifier}_alt"],
                $data["{$identifier}_title"],
                $data["{$identifier}_caption"],
                $data["{$identifier}_attribution"],
                $data["{$identifier}_css"],
                $data["{$identifier}_lazy_loading"],
                $data["{$identifier}_width"],
                $data["{$identifier}_height"],
                $data["{$identifier}_focal_x"],
                $data["{$identifier}_focal_y"]
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
                'focal_x'     => $metadata['focal_x'],
                'focal_y'     => $metadata['focal_y'],
            ]);

            $media->save();
        }
    }
}
