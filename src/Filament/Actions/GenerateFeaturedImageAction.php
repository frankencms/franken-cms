<?php

namespace FrankenCms\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use FrankenCms\Models\Post;
use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Services\AiImageService;
use FrankenCms\Settings\AiSettings;
use Illuminate\Support\Facades\Storage;

class GenerateFeaturedImageAction
{
    public static function make(string $name): Action
    {
        return Action::make($name)
            ->label('Generate with AI')
            ->icon('heroicon-o-sparkles')
            ->visible(function ($livewire) {
                if (! AiFeatureDetector::isImageAvailable()) {
                    return false;
                }

                return method_exists($livewire, 'getRecord') && $livewire->getRecord() !== null;
            })
            ->schema([
                Textarea::make('prompt')
                    ->label('Image prompt')
                    ->rows(4)
                    ->required()
                    ->default(function ($livewire) {
                        $data = $livewire->data ?? [];

                        return self::fillPromptTemplate(app(AiSettings::class)->featured_image_prompt, [
                            'title'   => $data['post_title'] ?? $data['title'] ?? '',
                            'excerpt' => $data['post_excerpt'] ?? $data['excerpt'] ?? '',
                        ]);
                    }),
            ])
            ->modalHeading('Generate Featured Image')
            ->modalSubmitActionLabel('Generate')
            ->action(function (array $data, $livewire) {
                $record = $livewire->getRecord();

                try {
                    self::generateAndAttach($record, $data['prompt']);

                    Notification::make()
                        ->title('Featured image generated')
                        ->success()
                        ->send();

                    // Refresh the form's media state so the new image shows immediately
                    if (method_exists($livewire, 'refreshFormData')) {
                        $livewire->refreshFormData(['featured_image']);
                    }
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Image generation failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Interpolate {placeholders} into the prompt template; unknown placeholders are stripped
     */
    public static function fillPromptTemplate(string $template, array $context): string
    {
        foreach ($context as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return trim(preg_replace('/\{[a-z_]+\}/', '', $template));
    }

    /**
     * Generate an image and attach it to the post's featured collection
     *
     * @throws Exception
     */
    public static function generateAndAttach(Post $record, string $prompt): void
    {
        $response = app(AiImageService::class)->generate($prompt);

        $generated = $response->firstImage();
        $extension = explode('/', $generated->mime())[1] ?? 'png';
        $tempName = uniqid('featured_', true) . '.' . $extension;

        // GeneratedImage::store() handles the base64 decode for us.
        $storedPath = $generated->storeAs('ai-featured', $tempName, 'local');

        try {
            // The 'featured' collection is registered with singleFile(), so
            // Spatie Media Library automatically removes the previous file.
            $record->addMedia(Storage::disk('local')->path($storedPath))
                ->toMediaCollection('featured');
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }
}
