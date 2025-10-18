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

    protected function getPromptContext(Get $get): array
    {
        return [
            'title'    => $get('../../post_title') ?? $get('../../title') ?? '',
            'content'  => $get('../../post_content') ?? $get('../../content') ?? '',
            'filename' => $get('file_name') ?? $get('name') ?? '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Alt text should be concise (max 125 characters per accessibility guidelines)
        $this->targetLength(10, 125);
    }
}
