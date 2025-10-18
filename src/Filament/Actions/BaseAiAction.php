<?php

namespace FrankenCms\Filament\Actions;


use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use FrankenCms\Services\AiFeatureDetector;

abstract class BaseAiAction extends Action
{
    protected ?int $targetMin = null;

    protected ?int $targetMax = null;

    /**
     * Get the action key for the prompt
     */
    abstract protected function getActionKey(): string;

    /**
     * Get the label for the prompt
     */
    abstract protected function getPromptLabel(): string;

    /**
     * Get the context data for the AI prompt
     *
     * @param  Get  $get  The form field getter
     * @param  mixed  $livewire  The Livewire component (for accessing record, etc.)
     */
    abstract protected function getPromptContext(Get $get, $livewire = null): array;

    /**
     * Get the field name to update
     * Override this in child classes to specify the correct field name
     */
    protected function getFieldName(): string
    {
        // Default: derive from action key by removing 'generate_' prefix
        $actionKey = $this->getActionKey();

        // Map action keys to field names
        $fieldMap = [
            'generate_seo_title' => 'seo_title',
            'generate_seo_description' => 'seo_description',
            'generate_teaser' => 'post_teaser',
            'generate_alt_text' => 'alt_text',
        ];

        return $fieldMap[$actionKey] ?? $actionKey;
    }

    /**
     * Set up the action
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ask Igor')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->tooltip('Generate content with AI')
            ->visible(fn () => AiFeatureDetector::isAvailable())
            ->extraAttributes([
                'class' => 'ai-action-button',
            ])
            ->action(function (Set $set, Get $get, $livewire) {
                // Get current field value
                $currentValue = $get($this->getFieldName());

                // Dispatch event to open modal
                $livewire->dispatch('open-ai-modal', [
                    'actionKey'     => $this->getActionKey(),
                    'promptLabel'   => $this->getPromptLabel(),
                    'context'       => $this->getPromptContext($get, $livewire),
                    'currentValue'  => $currentValue,
                    'targetMin'     => $this->targetMin,
                    'targetMax'     => $this->targetMax,
                    'fieldName'     => $this->getFieldName(),
                    'componentId'   => $livewire->getId(),
                ]);
            });

        // Listen for the generated content
        $this->registerListeners();
    }

    /**
     * Set character count target
     */
    public function targetLength(int $min, int $max): static
    {
        $this->targetMin = $min;
        $this->targetMax = $max;

        return $this;
    }

    /**
     * Register event listeners for AI generation
     */
    protected function registerListeners(): void
    {
        // Note: The actual listener will be in the form component
        // This is handled by Livewire's event system
    }

    /**
     * Extract plain text from RichEditor JSON content
     */
    protected function extractPlainText($content): string
    {
        if (empty($content)) {
            return '';
        }

        // If it's already a string, return it
        if (is_string($content) && !str_starts_with($content, '{')) {
            return $content;
        }

        // Try to decode JSON content from RichEditor
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $content = $decoded;
            }
        }

        // Extract text from Tiptap/ProseMirror JSON structure
        if (is_array($content)) {
            return $this->extractTextFromNodes($content);
        }

        return (string) $content;
    }

    /**
     * Recursively extract text from Tiptap nodes
     */
    private function extractTextFromNodes(array $node): string
    {
        $text = '';

        if (isset($node['text'])) {
            $text .= $node['text'];
        }

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                $text .= $this->extractTextFromNodes($child) . ' ';
            }
        }

        return trim($text);
    }
}
