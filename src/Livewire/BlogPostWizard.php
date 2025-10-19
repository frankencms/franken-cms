<?php

namespace FrankenCms\Livewire;

use Exception;
use FrankenCms\Services\AiService;
use Livewire\Attributes\On;
use Livewire\Component;

class BlogPostWizard extends Component
{
    public bool $isOpen = false;

    public int $step = 1;

    // Step 1: User inputs
    public string $focus = '';

    public string $audience = '';

    public string $notes = '';

    // Context from parent
    public string $currentTitle = '';

    public mixed $currentContent = null;

    public string $componentId = '';

    // Step 3: Generated content
    public ?string $generatedContent = null;

    public bool $generating = false;

    public ?string $error = null;

    public bool $confirmReplace = false;

    #[On('open-blog-post-wizard')]
    public function open($currentTitle = '', $currentContent = null, $componentId = ''): void
    {
        $this->currentTitle = $currentTitle;
        $this->currentContent = $currentContent;
        $this->componentId = $componentId;

        // Reset state
        $this->reset(['focus', 'audience', 'notes', 'generatedContent', 'error', 'confirmReplace']);
        $this->step = 1;
        $this->generating = false;
        $this->isOpen = true;
    }

    public function nextStep(): void
    {
        // Validate current step
        if ($this->step === 1) {
            $this->validate([
                'focus'    => 'required|min:3|max:500',
                'audience' => 'required|min:3|max:200',
            ]);

            // If content exists, go to confirmation step
            if ($this->hasExistingContent()) {
                $this->step = 2;
            } else {
                // Skip confirmation, go straight to generating
                $this->generateBlogPost();
            }
        } elseif ($this->step === 2) {
            // Validate confirmation
            if (! $this->confirmReplace) {
                $this->addError('confirmReplace', 'Please confirm you want to replace the existing content.');

                return;
            }
            $this->generateBlogPost();
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
            $this->error = null;
        }
    }

    public function generateBlogPost(): void
    {
        $this->step = 3;
        $this->generating = true;
        $this->error = null;

        try {
            $aiService = app(AiService::class);

            $context = [
                'title'    => $this->currentTitle,
                'focus'    => $this->focus,
                'audience' => $this->audience,
                'content'  => $this->notes,
            ];

            $this->generatedContent = $aiService->generate('generate_blog_post', $context);

            $this->generating = false;
            $this->step = 4; // Move to review step
        } catch (Exception $e) {
            $this->generating = false;
            $this->error = $e->getMessage();
            $this->step = 1; // Go back to start on error
        }
    }

    public function regenerate(): void
    {
        $this->generateBlogPost();
    }

    public function insertContent(): void
    {
        if ($this->generatedContent) {
            // Dispatch event to parent component to update the content
            $this->dispatch('insert-generated-content', [
                'content'     => $this->generatedContent,
                'componentId' => $this->componentId,
            ]);

            $this->close();
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset();
    }

    public function hasExistingContent(): bool
    {
        if (! $this->currentContent) {
            return false;
        }

        // Check if content is a JSON string (from RichEditor)
        if (is_string($this->currentContent) && str_starts_with($this->currentContent, '{')) {
            $decoded = json_decode($this->currentContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->currentContent = $decoded;
            }
        }

        // Count words in content
        $wordCount = $this->getContentWordCount();

        return $wordCount > 10; // Only show confirmation if more than 10 words
    }

    public function getContentWordCount(): int
    {
        if (! $this->currentContent) {
            return 0;
        }

        // Extract text from TipTap JSON
        $text = $this->extractTextFromContent($this->currentContent);

        return str_word_count($text);
    }

    protected function extractTextFromContent($content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content) && isset($content['type'])) {
            $text = '';

            if (isset($content['content']) && is_array($content['content'])) {
                foreach ($content['content'] as $node) {
                    $text .= $this->extractTextFromContent($node).' ';
                }
            }

            if (isset($content['text'])) {
                $text .= $content['text'].' ';
            }

            return $text;
        }

        return '';
    }

    public function render()
    {
        return view('franken-cms::livewire.blog-post-wizard');
    }
}
