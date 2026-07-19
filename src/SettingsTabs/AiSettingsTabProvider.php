<?php

namespace FrankenCms\SettingsTabs;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Services\AiImageService;
use FrankenCms\Services\AiModelService;
use FrankenCms\Services\AiService;
use FrankenCms\Settings\AiSettings;

class AiSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        $group = AiSettings::group();

        return Tab::make('Igor')
            ->icon('heroicon-o-sparkles')
            ->statePath($group)
            ->schema([

                // Check if laravel/ai is installed
                Section::make('Installation Required')
                    ->description('Igor requires the laravel/ai SDK to be installed.')
                    ->visible(fn () => ! AiFeatureDetector::isInstalled())
                    ->schema([
                        TextEntry::make('ai_sdk_not_installed')
                            ->label('')
                            ->markdown()
                            ->state(<<<'MD'
                            **Igor is not available** because the required package is not installed.

                            To enable Igor, run:
                            ```
                            composer require laravel/ai
                            ```

                            After installation, refresh this page to configure Igor.
                            MD),
                    ]),

                // Nested tabs for Provider and Prompts
                Tabs::make('igor-tabs')
                    ->visible(fn () => AiFeatureDetector::isInstalled())
                    ->tabs([

                        // Provider Configuration Tab
                        Tab::make('Provider')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([

                                Section::make('Igor Configuration')
                                    ->description('Configure Igor, your AI assistant, to help generate content.')
                                    ->schema([

                                        Toggle::make('enabled')
                                            ->label('Enable Igor')
                                            ->helperText('Turn on AI-powered content generation features')
                                            ->default(false)
                                            ->live()
                                            ->columnSpanFull(),

                                        TextEntry::make('ai_setup_notice')
                                            ->label('')
                                            ->markdown()
                                            ->state(
                                                '**No AI provider configured.** Add an API key to your `.env` '
                                                . '(e.g. `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, or `GEMINI_API_KEY`) '
                                                . 'and publish `config/ai.php` if you need to customize providers. '
                                                . 'Keys are no longer stored in the database.'
                                            )
                                            ->visible(fn () => empty(AiFeatureDetector::configuredProviders()))
                                            ->columnSpanFull(),

                                        Fieldset::make('Text Generation')
                                            ->schema([
                                                Select::make('text_provider')
                                                    ->label('Provider')
                                                    ->options(fn () => AiFeatureDetector::configuredProviders())
                                                    ->default('openai')
                                                    ->required()
                                                    ->live()
                                                    ->columnSpan(1),

                                                Select::make('text_model')
                                                    ->label('Model')
                                                    ->options(fn ($get) => $this->getModelsForProvider($get('text_provider')))
                                                    ->required()
                                                    ->searchable()
                                                    ->placeholder('Click "Refresh Models"')
                                                    ->helperText(fn ($get) => $this->getModelHelperText($get('text_provider')))
                                                    ->columnSpan(1),

                                                Actions::make([
                                                    Action::make('refresh_models')
                                                        ->label('Refresh Models')
                                                        ->icon('heroicon-o-arrow-path')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->action(function ($get, $set, $livewire) {
                                                            $this->refreshModels($get('text_provider'), $livewire);
                                                        })
                                                        ->visible(fn ($get) => array_key_exists($get('text_provider'), AiFeatureDetector::configuredProviders())),

                                                    Action::make('test_model')
                                                        ->label('Test Model')
                                                        ->icon('heroicon-o-bolt')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->action(function ($get) {
                                                            try {
                                                                app(AiService::class)->verifyTextModel($get('text_provider'), $get('text_model'));

                                                                Notification::make()
                                                                    ->title('Model responded ✓')
                                                                    ->body("Your key can use [{$get('text_model')}]. It's alive!")
                                                                    ->success()
                                                                    ->send();
                                                            } catch (Exception $e) {
                                                                Notification::make()
                                                                    ->title('Model test failed')
                                                                    ->body($e->getMessage())
                                                                    ->danger()
                                                                    ->persistent()
                                                                    ->send();
                                                            }
                                                        })
                                                        ->visible(fn ($get) => filled($get('text_model')) && array_key_exists($get('text_provider'), AiFeatureDetector::configuredProviders())),
                                                ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->visible(fn ($get) => $get('enabled') && ! empty(AiFeatureDetector::configuredProviders())),

                                        Fieldset::make('Image Generation')
                                            ->schema([
                                                TextEntry::make('image_provider_notice')
                                                    ->label('')
                                                    ->markdown()
                                                    ->state(
                                                        '**No image-capable provider configured.** Add e.g. '
                                                        . '`OPENAI_API_KEY` or `GEMINI_API_KEY` to your `.env`.'
                                                    )
                                                    ->visible(fn () => empty(AiFeatureDetector::imageCapableProviders()))
                                                    ->columnSpanFull(),

                                                Select::make('image_provider')
                                                    ->label('Provider')
                                                    ->options(fn () => AiFeatureDetector::imageCapableProviders())
                                                    ->placeholder('Auto (first configured image-capable provider)')
                                                    ->nullable()
                                                    ->live()
                                                    ->afterStateUpdated(fn ($set) => $set('image_model', null))
                                                    ->columnSpan(1),

                                                Select::make('image_model')
                                                    ->label('Model')
                                                    ->options(fn ($get) => app(AiModelService::class)->imageModelsForProvider($get('image_provider') ?? ''))
                                                    ->placeholder('Provider default')
                                                    ->nullable()
                                                    ->helperText('Leave empty for the provider default model')
                                                    ->visible(fn ($get) => filled($get('image_provider')))
                                                    ->columnSpan(1),

                                                Select::make('image_quality')
                                                    ->label('Quality')
                                                    ->options([
                                                        'low'    => 'Low',
                                                        'medium' => 'Medium',
                                                        'high'   => 'High',
                                                    ])
                                                    ->default('medium')
                                                    ->columnSpan(1),

                                                Actions::make([
                                                    Action::make('test_image_model')
                                                        ->label('Test Model')
                                                        ->icon('heroicon-o-bolt')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Test image generation?')
                                                        ->modalDescription('This generates one small low-quality image with the selected provider/model — your provider will charge for it (typically a cent or two).')
                                                        ->modalSubmitActionLabel('Generate test image')
                                                        ->action(function ($get) {
                                                            try {
                                                                app(AiImageService::class)->verifyImageModel($get('image_provider'), $get('image_model'));

                                                                $label = $get('image_provider') ? "[{$get('image_provider')}]" : 'the auto-selected provider';

                                                                Notification::make()
                                                                    ->title('Image model responded ✓')
                                                                    ->body("Your key can generate images with {$label}. It's alive!")
                                                                    ->success()
                                                                    ->send();
                                                            } catch (Exception $e) {
                                                                Notification::make()
                                                                    ->title('Image model test failed')
                                                                    ->body($e->getMessage())
                                                                    ->danger()
                                                                    ->persistent()
                                                                    ->send();
                                                            }
                                                        })
                                                        ->visible(fn () => ! empty(AiFeatureDetector::imageCapableProviders())),
                                                ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull()
                                            ->visible(fn ($get) => $get('enabled') && ! empty(AiFeatureDetector::configuredProviders())),

                                    ])
                                    ->columns(2),

                            ]),

                        // Prompts Management Tab
                        Tab::make('Prompts')
                            ->icon('heroicon-o-document-text')
                            ->schema([

                                Section::make('Prompt Configuration')
                                    ->description('Configure Igor\'s prompts for different content generation tasks. Use placeholders like {title}, {content}, {excerpt}, and {filename}.')
                                    ->schema([

                                        // SEO Title Generator
                                        Section::make('SEO Title Generator')
                                            ->description('Generate SEO-optimized titles (50-60 characters)')
                                            ->schema([
                                                Toggle::make('seo_title_enabled')
                                                    ->label('Enable SEO Title Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('seo_title_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {title}, {content}')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('seo_title_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // SEO Meta Description
                                        Section::make('SEO Meta Description')
                                            ->description('Generate SEO meta descriptions (150-160 characters)')
                                            ->schema([
                                                Toggle::make('seo_description_enabled')
                                                    ->label('Enable SEO Description Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('seo_description_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {title}, {content}')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('seo_description_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // Post Teaser/Excerpt
                                        Section::make('Post Teaser/Excerpt')
                                            ->description('Create compelling teasers for blog posts (2-3 sentences)')
                                            ->schema([
                                                Toggle::make('teaser_enabled')
                                                    ->label('Enable Teaser Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('teaser_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {content}')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('teaser_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // Image Alt Text
                                        Section::make('Image Alt Text')
                                            ->description('Generate descriptive alt text for images (accessibility)')
                                            ->schema([
                                                Toggle::make('alt_text_enabled')
                                                    ->label('Enable Alt Text Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('alt_text_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {title}, {content}, {filename}')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('alt_text_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // Image Title
                                        Section::make('Image Title')
                                            ->description('Generate descriptive titles for images (hover text)')
                                            ->schema([
                                                Toggle::make('image_title_enabled')
                                                    ->label('Enable Image Title Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('image_title_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {title}, {content}, {filename}')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('image_title_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // Full Blog Post Generator
                                        Section::make('Full Blog Post Generator')
                                            ->description('Generate complete, SEO-optimized blog posts (800-1200 words)')
                                            ->schema([
                                                Toggle::make('blog_post_enabled')
                                                    ->label('Enable Blog Post Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('blog_post_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {title}, {focus}, {audience}, {content}. Note: {focus} and {audience} require user input.')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('blog_post_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // Blog Post Title Generator
                                        Section::make('Blog Post Title Generator')
                                            ->description('Generate compelling, SEO-optimized blog post titles (50-60 characters)')
                                            ->schema([
                                                Toggle::make('blog_post_title_enabled')
                                                    ->label('Enable Blog Post Title Generation')
                                                    ->default(true)
                                                    ->columnSpanFull(),

                                                CodeEditor::make('blog_post_title_prompt')
                                                    ->label('Prompt Template')
                                                    ->helperText('Available placeholders: {title}, {content}')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('blog_post_title_enabled')),
                                            ])
                                            ->collapsible()
                                            ->collapsed(),

                                        // Featured Image Generation
                                        Section::make('Featured Image Generation')
                                            ->description('Generate a featured image with an image-capable AI provider when a post has none')
                                            ->schema([
                                                Toggle::make('featured_image_enabled')
                                                    ->label('Featured Image Generation')
                                                    ->helperText('Generate featured images with an image-capable AI model')
                                                    ->default(true)
                                                    ->live()
                                                    ->columnSpanFull(),

                                                Textarea::make('featured_image_prompt')
                                                    ->label('Image Prompt Template')
                                                    ->helperText('Pre-fills the generation prompt. Placeholders: {title}, {excerpt}. Provider, model, and quality are configured in the Provider tab under Image Generation.')
                                                    ->columnSpanFull()
                                                    ->visible(fn ($get) => $get('featured_image_enabled')),
                                            ])
                                            ->columns(2)
                                            ->collapsible()
                                            ->collapsed(),

                                    ]),

                            ]),

                    ])
                    ->columnSpanFull(),

            ]);
    }

    public function getSettingsClass(): string
    {
        return AiSettings::class;
    }

    public function getOrder(): int
    {
        return 70;
    }

    public function getTabKey(): string
    {
        return 'igor';
    }

    /**
     * Get available models for a provider
     * Uses dynamic fetching when cached, falls back to config
     */
    protected function getModelsForProvider(?string $provider): array
    {
        if (! $provider) {
            return [];
        }

        // Use AiModelService for dynamic model fetching
        $modelService = app(AiModelService::class);

        return $modelService->getModelsForProvider($provider);
    }

    /**
     * Get helper text for the model field
     */
    protected function getModelHelperText(?string $provider): string
    {
        if (! $provider) {
            return 'Choose the AI model to use for generation';
        }

        $modelService = app(AiModelService::class);

        if ($modelService->hasCachedModels($provider)) {
            return 'Models loaded from ' . ucfirst($provider) . ' API (cached)';
        }

        return 'Click "Refresh Models" to load available models';
    }

    /**
     * Refresh models from provider API
     */
    protected function refreshModels(?string $provider, $livewire): void
    {
        if (! $provider || ! array_key_exists($provider, AiFeatureDetector::configuredProviders())) {
            Notification::make()
                ->title('Missing Information')
                ->body('Please select a configured provider first.')
                ->warning()
                ->send();

            return;
        }

        try {
            $modelService = app(AiModelService::class);
            $models = $modelService->refreshModels($provider);

            $count = count($models);

            Notification::make()
                ->title('Models Refreshed')
                ->body("Successfully loaded {$count} models from " . ucfirst($provider) . '.')
                ->success()
                ->send();

            // Force Livewire to re-render the form to show new model options
            $livewire->dispatch('$refresh');

        } catch (Exception $e) {
            Notification::make()
                ->title('Failed to Refresh Models')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
