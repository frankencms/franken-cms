<?php

namespace FrankenCms\Filament\Actions;

use Filament\Schemas\Components\Utilities\Get;

class GenerateSeoDescriptionAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_seo_description';
    }

    protected function getPromptLabel(): string
    {
        return 'SEO Meta Description';
    }

    protected function getPromptContext(Get $get): array
    {
        return [
            'title'   => $get('post_title') ?? $get('title') ?? '',
            'content' => $this->extractPlainText($get('post_content') ?? $get('content') ?? ''),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set character target for meta descriptions (150-160 characters)
        $this->targetLength(150, 160);
    }
}
