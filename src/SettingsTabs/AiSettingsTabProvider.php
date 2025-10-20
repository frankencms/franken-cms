<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Settings\AiSettings;

class AiSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Igor')
            ->icon('heroicon-o-sparkles')
            ->visible(fn () => AiFeatureDetector::isPrismInstalled())
            ->schema([

                // Check if Prism is installed
                Section::make('Installation Required')
                    ->description('Igor requires the Prism PHP package to be installed.')
                    ->visible(fn () => ! AiFeatureDetector::isPrismInstalled())
                    ->schema([
                        TextEntry::make('prism_not_installed')
                            ->label('')
                            ->state('
                                **Igor is not available** because the required package is not installed.

                                To enable Igor, run:
                                ```bash
                                composer require prism-php/prism
                                ```

                                After installation, refresh this page to configure Igor.
                            '),
                    ]),

                // Nested tabs for Provider and Prompts
                Tabs::make('igor-tabs')
                    ->visible(fn () => AiFeatureDetector::isPrismInstalled())
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

                                        Select::make('provider')
                                            ->label('AI Provider')
                                            ->options(fn () => $this->getProviderOptions())
                                            ->default('openai')
                                            ->required()
                                            ->live()
                                            ->visible(fn ($get) => $get('enabled'))
                                            ->columnSpan(1),

                                        TextInput::make('api_key')
                                            ->label('API Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Your API key will be encrypted and stored securely')
                                            ->required()
                                            ->visible(fn ($get) => $get('enabled'))
                                            ->columnSpan(1),

                                        Select::make('model')
                                            ->label('Model')
                                            ->options(fn ($get) => $this->getModelsForProvider($get('provider')))
                                            ->default('gpt-4o')
                                            ->required()
                                            ->helperText('Choose the AI model to use for generation')
                                            ->visible(fn ($get) => $get('enabled'))
                                            ->columnSpan(1),

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
     * Get provider options from config
     */
    protected function getProviderOptions(): array
    {
        $providers = config('franken-cms.ai_providers', []);

        return collect($providers)
            ->mapWithKeys(fn ($config, $key) => [$key => $config['label'] ?? ucfirst($key)])
            ->toArray();
    }

    /**
     * Get available models for a provider from config
     */
    protected function getModelsForProvider(?string $provider): array
    {
        if (! $provider) {
            return [];
        }

        $models = config("franken-cms.ai_providers.{$provider}.models", []);

        // If no models found for this provider, return default
        if (empty($models)) {
            return ['gpt-5-chat-latest' => 'GPT-5 (Recommended)'];
        }

        return $models;
    }
}
