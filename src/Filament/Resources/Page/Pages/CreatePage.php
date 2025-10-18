<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Page\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

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

        return $data;
    }
}
