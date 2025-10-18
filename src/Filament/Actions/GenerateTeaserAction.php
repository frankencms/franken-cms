<?php

namespace FrankenCms\Filament\Actions;

use Filament\Schemas\Components\Utilities\Get;

class GenerateTeaserAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_teaser';
    }

    protected function getPromptLabel(): string
    {
        return 'Post Teaser';
    }

    protected function getPromptContext(Get $get): array
    {
        return [
            'content' => $get('post_content') ?? $get('content') ?? '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // No strict character limit for teasers, but suggest a reasonable range
        $this->targetLength(100, 200);
    }
}
