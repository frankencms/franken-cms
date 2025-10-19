<?php

namespace FrankenCms\Filament\Actions;

use Filament\Actions\Action;
use FrankenCms\Livewire\BlogPostWizard;
use FrankenCms\Services\AiFeatureDetector;

class GenerateBlogPostAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ask Igor')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->tooltip('Generate a complete blog post with AI')
            ->visible(fn () => AiFeatureDetector::isAvailable())
            ->action(function ($livewire) {
                // Get current content to check if we need confirmation
                $currentContent = $livewire->data['post_content'] ?? null;
                $currentTitle = $livewire->data['post_title'] ?? '';

                // Dispatch event to open the wizard modal (as browser event to reach the global component)
                $livewire->dispatch('open-blog-post-wizard',
                    currentTitle: $currentTitle,
                    currentContent: $currentContent,
                    componentId: $livewire->getId()
                )->to(BlogPostWizard::class);
            });
    }
}
