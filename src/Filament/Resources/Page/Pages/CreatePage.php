<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Page\PageResource;
use Illuminate\Database\Eloquent\Model;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected ?string $pendingTemplate = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract template from form data and store it temporarily
        if (isset($data['template'])) {
            $this->pendingTemplate = $data['template'];
            unset($data['template']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Save template to meta after the page is created
        if ($this->pendingTemplate !== null && $this->record) {
            $this->record->setMeta('template', $this->pendingTemplate);
        }
    }
}
