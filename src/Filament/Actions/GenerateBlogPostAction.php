<?php

namespace FrankenCms\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Services\AiService;
use Illuminate\Support\Str;

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
            ->modalWidth('2xl')
            ->modalIcon('frankencms-igor')
            ->modalHeading('Ask Igor to Write Your Blog Post')
            ->modalDescription('Igor shall labor diligently to conjure a full treatise from your specifications.')
            ->schema(function ($livewire) {
                $currentContent = $livewire->data['post_content'] ?? null;
                $hasContent = $this->hasExistingContent($currentContent);

                $schema = [
                    // Igor loading overlay
                    ViewField::make('igor_loading')
                        ->view('franken-cms::filament.actions.igor-loading')
                        ->label(''),

                    TextInput::make('focus')
                        ->label('Topic / Focus')
                        ->required()
                        ->minLength(3)
                        ->maxLength(500)
                        ->placeholder('e.g., "10 productivity tips for remote workers"')
                        ->helperText('The main topic or angle of your blog post'),

                    TextInput::make('audience')
                        ->label('Target Audience')
                        ->required()
                        ->minLength(3)
                        ->maxLength(200)
                        ->placeholder('e.g., "Busy professionals working from home"')
                        ->helperText('Who is this blog post for?'),

                    Textarea::make('notes')
                        ->label('Key Points or Notes')
                        ->rows(4)
                        ->placeholder("• Focus on practical tools\n• Include time-saving strategies\n• Mention async communication")
                        ->helperText('Specific points, keywords, or ideas to include'),
                ];

                // Add warning if replacing existing content
                if ($hasContent) {
                    $wordCount = $this->getContentWordCount($currentContent);
                    $preview = Str::limit($this->extractTextFromContent($currentContent), 200);

                    $schema[] = ViewField::make('warning')
                        ->view('franken-cms::filament.actions.content-warning', [
                            'wordCount' => $wordCount,
                            'preview'   => $preview,
                        ]);

                    $schema[] = Checkbox::make('confirmReplace')
                        ->label('I understand this will replace my current content')
                        ->accepted()
                        ->validationMessages([
                            'accepted' => 'Please confirm you want to replace the existing content.',
                        ]);
                }

                return $schema;
            })
            ->modalSubmitActionLabel('Generate Blog Post')
            ->action(function (array $data, $livewire, Action $action) {
                try {
                    $aiService = app(AiService::class);

                    $context = [
                        'title'    => $livewire->data['post_title'] ?? '',
                        'focus'    => $data['focus'],
                        'audience' => $data['audience'],
                        'content'  => $data['notes'] ?? '',
                    ];

                    // Generate content
                    $generatedContent = $aiService->generate('generate_blog_post', $context);

                    // Convert markdown to HTML
                    $htmlContent = Str::markdown($generatedContent);

                    // Insert into RichEditor
                    $livewire->data['post_content'] = $htmlContent;

                    // Success notification (shows after modal closes)
                    Notification::make()
                        ->title('It\'s alive!')
                        ->body('Igor has toiled through ink and inspiration, behold, your blog post stands complete.')
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    // Error notification
                    Notification::make()
                        ->title('Igor encountered a problem')
                        ->body("By thunder! A calamity has struck the laboratory: {$e->getMessage()}")
                        ->danger()
                        ->persistent()
                        ->send();

                    // Re-throw to prevent modal from closing
                    throw $e;
                }
            });
    }

    protected function hasExistingContent(mixed $content): bool
    {
        if (! $content) {
            return false;
        }

        return $this->getContentWordCount($content) > 10;
    }

    protected function getContentWordCount(mixed $content): int
    {
        if (! $content) {
            return 0;
        }

        $text = $this->extractTextFromContent($content);

        return str_word_count($text);
    }

    protected function extractTextFromContent(mixed $content): string
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
}
