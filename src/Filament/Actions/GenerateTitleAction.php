<?php

namespace FrankenCms\Filament\Actions;

use Filament\Schemas\Components\Utilities\Get;

class GenerateTitleAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'blog_post_title';
    }

    protected function getPromptLabel(): string
    {
        return 'Blog Post Title';
    }

    protected function getPromptContext(Get $get, $livewire = null): array
    {
        // Get current title (if any) and content for context
        $title = $get('post_title') ?? $get('title') ?? '';
        $content = $livewire->data['post_content'] ?? $livewire->data['content'] ?? '';

        // Extract plain text from rich editor content if needed
        $content = $this->extractPlainText($content);

        return [
            'title'   => $title,
            'content' => $content,
        ];
    }

    protected function getFieldName(): string
    {
        return 'post_title';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Blog post titles should be 50-60 characters for SEO
        $this->targetLength(50, 60);
    }
}
