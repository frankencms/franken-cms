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
     */
    abstract protected function getPromptContext(Get $get): array;

    /**
     * Get the field name to update
     */
    protected function getFieldName(): string
    {
        return $this->getName();
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
                    'actionKey'    => $this->getActionKey(),
                    'promptLabel'  => $this->getPromptLabel(),
                    'context'      => $this->getPromptContext($get),
                    'currentValue' => $currentValue,
                    'targetMin'    => $this->targetMin,
                    'targetMax'    => $this->targetMax,
                    'fieldName'    => $this->getFieldName(),
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
}
