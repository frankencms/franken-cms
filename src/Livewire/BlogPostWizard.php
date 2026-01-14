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

    // Form data
    public string $focus = '';

    public string $audience = '';

    public string $notes = '';

    public bool $confirmReplace = false;

    // Context from parent
    public string $currentTitle = '';

    public mixed $currentContent = null;

    public string $componentId = '';

    // Generated content
    public ?string $generatedContent = null;

    public bool $generating = false;

    public ?string $error = null;

    public bool $isTransitioning = false;

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
        $this->isTransitioning = true;

        // Validate current step
        if ($this->step === 1) {
            $this->validate([
                'focus'    => 'required|min:3|max:500',
                'audience' => 'required|min:3|max:200',
            ]);

            // If content exists, go to confirmation step
            if ($this->hasExistingContent()) {
                $this->step = 2;
                $this->isTransitioning = false;
            } else {
                // Skip confirmation, move to generating step
                $this->step = 3;
                $this->isTransitioning = false;
            }
        } elseif ($this->step === 2) {
            // Validate confirmation
            if (! $this->confirmReplace) {
                $this->addError('confirmReplace', 'Please confirm you want to replace the existing content.');
                $this->isTransitioning = false;

                return;
            }
            // Move to generating step
            $this->step = 3;
            $this->isTransitioning = false;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            // Skip confirmation step if going back and no content
            if ($this->step === 3 && ! $this->hasExistingContent()) {
                $this->step = 1;
            } else {
                $this->step--;
            }
            $this->error = null;
        }
    }

    public function generateBlogPost(): void
    {
        // Prevent duplicate calls while already generating
        if ($this->generating) {
            return;
        }

        $this->step = 3;
        $this->generating = true;
        $this->isTransitioning = false;
        $this->error = null;
        $this->generatedContent = ''; // Reset content for streaming

        try {
            $aiService = app(AiService::class);

            $context = [
                'title'    => $this->currentTitle,
                'focus'    => $this->focus,
                'audience' => $this->audience,
                'content'  => $this->notes,
            ];

            // Use streaming to show content as it's generated
            $this->generatedContent = $aiService->generate('generate_blog_post', $context, function ($chunk) {
                $this->generatedContent .= $chunk;
                $this->stream(to: 'generatedContent', content: $this->generatedContent);
            });

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
        // Reset state before regenerating
        $this->generatedContent = null;
        $this->generating = false;

        $this->generateBlogPost();
    }

    public function insertContent(): void
    {
        if ($this->generatedContent) {
            // Convert markdown to HTML for RichEditor
            $htmlContent = \Illuminate\Support\Str::markdown($this->generatedContent);

            // Dispatch event globally - the EditPost page will listen for it
            $this->dispatch(
                'insert-generated-content',
                content: $htmlContent,
                componentId: $this->componentId
            );

            $this->close();
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
        // Don't reset - preserve generated content in case user wants to insert later
        // Only reset the form inputs and errors
        $this->reset(['focus', 'audience', 'notes', 'error', 'confirmReplace', 'isTransitioning']);
        $this->step = 1;
        $this->generating = false;
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
                    $text .= $this->extractTextFromContent($node) . ' ';
                }
            }

            if (isset($content['text'])) {
                $text .= $content['text'] . ' ';
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
