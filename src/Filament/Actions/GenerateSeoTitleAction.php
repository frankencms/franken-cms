<?php

namespace FrankenCms\Filament\Actions;

use Filament\Schemas\Components\Utilities\Get;

class GenerateSeoTitleAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_seo_title';
    }

    protected function getPromptLabel(): string
    {
        return 'SEO Title Generator';
    }

    protected function getPromptContext(Get $get, $livewire = null): array
    {
        return [
            'title'   => $get('post_title') ?? $get('title') ?? '',
            'content' => $get('post_content') ?? $get('content') ?? '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set character target for SEO titles (50-60 characters)
        $this->targetLength(50, 60);
    }
}
