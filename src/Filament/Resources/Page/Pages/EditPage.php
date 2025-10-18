<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Page\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

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
}
