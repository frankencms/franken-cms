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
                                            ->options([
                                                'openai'    => 'OpenAI (GPT-5)',
                                                'anthropic' => 'Anthropic (Claude)',
                                                'ollama'    => 'Ollama (Local)',
                                            ])
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
                                            ->visible(fn ($get) => $get('enabled') && $get('provider') !== 'ollama')
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

                                Section::make('Setup Instructions')
                                    ->description('Get started with your chosen AI provider')
                                    ->visible(fn ($get) => $get('enabled'))
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        TextEntry::make('openai_instructions')
                                            ->label('OpenAI Setup')
                                            ->state('
                                                1. Visit https://platform.openai.com/api-keys
                                                2. Create a new API key
                                                3. Copy and paste it above
                                                4. Recommended model: **gpt-4o** (balanced) or **gpt-4o-mini** (faster, cheaper)
                                            ')
                                            ->visible(fn ($get) => $get('provider') === 'openai'),

                                        TextEntry::make('anthropic_instructions')
                                            ->label('Anthropic Setup')
                                            ->state('
                                                1. Visit https://console.anthropic.com/
                                                2. Create a new API key
                                                3. Copy and paste it above
                                                4. Recommended model: **claude-3-5-sonnet-20241022** (best quality)
                                            ')
                                            ->visible(fn ($get) => $get('provider') === 'anthropic'),

                                        TextEntry::make('ollama_instructions')
                                            ->label('Ollama Setup')
                                            ->state('
                                                1. Install Ollama: https://ollama.ai
                                                2. Download a model: `ollama pull llama2`
                                                3. Make sure Ollama is running
                                                4. No API key needed - runs locally on your machine
                                            ')
                                            ->visible(fn ($get) => $get('provider') === 'ollama'),
                                    ]),

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
     */
    protected function getModelsForProvider(?string $provider): array
    {
        return match ($provider) {
            'openai' => [
                'gpt-5-chat-latest' => 'GPT-5 (Recommended)',
                'gpt-4o'            => 'GPT-4o',
                'gpt-4o-mini'       => 'GPT-4o Mini (Faster, Cheaper)',
                'gpt-4-turbo'       => 'GPT-4 Turbo',
                'gpt-4'             => 'GPT-4',
                'gpt-3.5-turbo'     => 'GPT-3.5 Turbo',
            ],
            'anthropic' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Recommended)',
                'claude-3-opus-20240229'     => 'Claude 3 Opus',
                'claude-3-sonnet-20240229'   => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307'    => 'Claude 3 Haiku',
            ],
            'ollama' => [
                'llama2'    => 'Llama 2',
                'mistral'   => 'Mistral',
                'codellama' => 'Code Llama',
                'phi'       => 'Phi',
            ],
            default => ['gpt-5-chat-latest' => 'GPT-5 (Recommended)'],
        };
    }
}
