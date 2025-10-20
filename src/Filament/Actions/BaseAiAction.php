<?php

namespace FrankenCms\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Services\AiService;

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
            'generate_seo_title'       => 'seo_title',
            'generate_seo_description' => 'seo_description',
            'generate_teaser'          => 'post_teaser',
            'generate_alt_text'        => 'featured_image_alt',
            'generate_image_title'     => 'featured_image_title',
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
            ->requiresConfirmation(false)
            ->action(function (Get $get, $livewire) {
                try {
                    $aiService = app(AiService::class);

                    $context = $this->getPromptContext($get, $livewire);

                    // Generate content
                    $generatedText = $aiService->generate($this->getActionKey(), $context);

                    // Get character count
                    $characterCount = mb_strlen($generatedText);

                    // Update the field
                    $fieldName = $this->getFieldName();
                    $livewire->data[$fieldName] = $generatedText;

                    // Determine if character count is in target range
                    $inRange = true;
                    if ($this->targetMin && $this->targetMax) {
                        $inRange = $characterCount >= $this->targetMin && $characterCount <= $this->targetMax;
                    }

                    // Success notification
                    $notification = Notification::make()
                        ->title('Content generated!')
                        ->success();

                    if ($this->targetMin && $this->targetMax) {
                        if ($inRange) {
                            $notification->body("Igor crafted {$characterCount} characters - perfect! ✨");
                        } else {
                            $notification
                                ->body("Igor crafted {$characterCount} characters (target: {$this->targetMin}-{$this->targetMax})")
                                ->warning();
                        }
                    } else {
                        $notification->body('Igor has completed your request. 🧟‍♂️');
                    }

                    $notification->send();
                } catch (Exception $e) {
                    // Error notification
                    Notification::make()
                        ->title('Igor encountered a problem')
                        ->body("By thunder! A calamity in the lab: {$e->getMessage()}")
                        ->danger()
                        ->persistent()
                        ->send();

                    throw $e;
                }
            });
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
     * Extract plain text from RichEditor JSON content
     */
    protected function extractPlainText($content): string
    {
        if (empty($content)) {
            return '';
        }

        // If it's already a string, return it
        if (is_string($content) && ! str_starts_with($content, '{')) {
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
