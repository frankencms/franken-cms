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
        // Use $livewire->data directly to avoid Schema object issues with nested paths
        $title = $livewire->data['post_title'] ?? $livewire->data['title'] ?? '';
        $content = $livewire->data['post_content'] ?? $livewire->data['content'] ?? '';

        // Extract plain text from rich editor content if needed
        $content = $this->extractPlainText($content);

        $context = [
            'title'    => $title,
            'content'  => $content,
            'filename' => $get('file_name') ?? $get('name') ?? '',
        ];

        // Try to get the image from the record's media
        if ($livewire && method_exists($livewire, 'getRecord')) {
            $record = $livewire->getRecord();

            if ($record && method_exists($record, 'hasMedia') && $record->hasMedia('featured')) {
                $media = $record->getFirstMedia('featured');
                if ($media) {
                    // Provide both URL (for production) and path (for local development)
                    // The AiService will use URL if accessible, otherwise local path
                    $context['image_url'] = $media->getUrl();
                    $context['image_path'] = $media->getPath();
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
