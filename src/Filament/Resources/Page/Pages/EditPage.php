<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Page\PageResource;
use FrankenCms\Models\Page;
use FrankenCms\Registries\FieldRegistry;
use FrankenCms\Services\CmsFieldParser;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected array $customImageFieldsMetadata = [];

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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
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

        $parser = new CmsFieldParser;
        $parser->parse($templatePath);

        $fields = FieldRegistry::getFields();

        // Load metadata for each media_image field
        foreach ($fields as $identifier => $field) {
            if ($field['type'] !== 'media_image') {
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
                $data["{$identifier}_width"] = $metadata['width'] ?? null;
                $data["{$identifier}_height"] = $metadata['height'] ?? null;
                $data["{$identifier}_focal_x"] = $metadata['focal_x'] ?? 50;
                $data["{$identifier}_focal_y"] = $metadata['focal_y'] ?? 50;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

    protected function afterSave(): void
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
