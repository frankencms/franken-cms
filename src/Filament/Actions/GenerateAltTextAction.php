<?php

namespace FrankenCms\Filament\Actions;

use Filament\Schemas\Components\Utilities\Get;

class GenerateAltTextAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_alt_text';
    }

    protected function getPromptLabel(): string
    {
        return 'Image Alt Text';
    }

    protected function getPromptContext(Get $get, $livewire = null): array
    {
        $context = [
            'title'    => $get('../../post_title') ?? $get('../../title') ?? '',
            'content'  => $get('../../post_content') ?? $get('../../content') ?? '',
            'filename' => $get('file_name') ?? $get('name') ?? '',
        ];

        // Try to get the image URL from the record's media
        if ($livewire && method_exists($livewire, 'getRecord')) {
            $record = $livewire->getRecord();

            if ($record && method_exists($record, 'hasMedia') && $record->hasMedia('featured')) {
                $media = $record->getFirstMedia('featured');
                if ($media) {
                    $context['image_url'] = $media->getUrl();
                }
            }
        }

        return $context;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Alt text should be concise (max 125 characters per accessibility guidelines)
        $this->targetLength(10, 125);
    }
}
