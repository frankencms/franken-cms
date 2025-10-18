<?php

namespace FrankenCms\Filament\Components;

use Exception;
use Filament\Notifications\Notification;
use FrankenCms\Services\AiService;
use Livewire\Attributes\On;
use Livewire\Component;

class AiGeneratorModal extends Component
{
    // Modal state
    public bool $isOpen = false;

    // Configuration
    public string $actionKey = '';

    public string $promptLabel = '';

    public array $context = [];

    public ?string $currentValue = null;

    public ?int $targetMin = null;

    public ?int $targetMax = null;

    public string $fieldName = '';

    // Generation state
    public ?string $generatedText = null;

    public bool $isGenerating = false;

    public ?string $error = null;

    public int $characterCount = 0;

    /**
     * Mount the component
     */
    public function mount(): void
    {
        $this->reset(['generatedText', 'isGenerating', 'error', 'characterCount']);
    }

    /**
     * Open the modal
     */
    #[On('open-ai-modal')]
    public function openModal(array $payload): void
    {
        \Log::info('AI Modal openModal called', $payload);

        $this->actionKey = $payload['actionKey'] ?? '';
        $this->promptLabel = $payload['promptLabel'] ?? '';
        $this->context = $payload['context'] ?? [];
        $this->currentValue = $payload['currentValue'] ?? null;
        $this->targetMin = $payload['targetMin'] ?? null;
        $this->targetMax = $payload['targetMax'] ?? null;
        $this->fieldName = $payload['fieldName'] ?? '';
        $this->isOpen = true;

        \Log::info('AI Modal isOpen set to', ['isOpen' => $this->isOpen]);

        $this->reset(['generatedText', 'isGenerating', 'error', 'characterCount']);
    }

    /**
     * Generate content using AI
     */
    public function generate(): void
    {
        $this->isGenerating = true;
        $this->error = null;

        try {
            $aiService = app(AiService::class);
            $this->generatedText = $aiService->generate($this->actionKey, $this->context);
            $this->characterCount = mb_strlen($this->generatedText);

            Notification::make()
                ->success()
                ->title('Content Generated')
                ->body('Igor has created your content!')
                ->send();
        } catch (Exception $e) {
            $this->error = $e->getMessage();

            Notification::make()
                ->danger()
                ->title('Generation Failed')
                ->body($e->getMessage())
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * Regenerate content
     */
    public function regenerate(): void
    {
        $this->generate();
    }

    /**
     * Accept the generated content and close modal
     */
    public function useGeneration(): void
    {
        if (! $this->generatedText) {
            return;
        }

        // Emit event to update the field
        $this->dispatch('ai-content-generated', [
            'fieldName' => $this->fieldName,
            'value'     => $this->generatedText,
        ]);

        Notification::make()
            ->success()
            ->title('Content Applied')
            ->body('The generated content has been added to your field.')
            ->send();

        $this->close();
    }

    /**
     * Close the modal
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['generatedText', 'isGenerating', 'error', 'characterCount']);
    }

    /**
     * Get character count color based on target
     */
    public function getCharacterCountColor(): string
    {
        if (! $this->targetMin || ! $this->targetMax) {
            return 'gray';
        }

        if ($this->characterCount === 0) {
            return 'gray';
        }

        if ($this->characterCount < $this->targetMin) {
            return 'danger';
        }

        if ($this->characterCount >= $this->targetMin && $this->characterCount <= $this->targetMax) {
            return 'success';
        }

        return 'warning';
    }

    /**
     * Render the component
     */
    public function render(): mixed
    {
        return view('franken-cms::filament.components.ai-generator-modal');
    }
}
