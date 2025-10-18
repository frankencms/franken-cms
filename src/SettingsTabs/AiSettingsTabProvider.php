<?php

namespace FrankenCms\SettingsTabs;

use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Prompts\DefaultPrompts;
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

                                Section::make('Igor\'s Instructions')
                                    ->description('Customize how Igor generates content. Edit prompts or add your own.')
                                    ->schema([

                                        TextEntry::make('prompts_help')
                                            ->label('')
                                            ->state('
                                                **Available Placeholders:**
                                                - `{title}` - Post/page title
                                                - `{content}` - Post/page content
                                                - `{excerpt}` - Current excerpt
                                                - `{filename}` - Image filename (for alt text)

                                                **Tips:**
                                                - Be specific in your instructions
                                                - Include desired length/format
                                                - Adjust temperature for creativity (0 = focused, 1 = creative)
                                                - Higher max tokens = longer responses
                                            ')
                                            ->columnSpanFull(),

                                        Repeater::make('prompts')
                                            ->label('Prompt Templates')
                                            ->schema([

                                                TextInput::make('label')
                                                    ->label('Label')
                                                    ->required()
                                                    ->placeholder('e.g., "SEO Title Generator"')
                                                    ->helperText('Display name shown to users')
                                                    ->columnSpan(1),

                                                TextInput::make('action_key')
                                                    ->label('Action Key')
                                                    ->required()
                                                    ->alphaDash()
                                                    ->placeholder('e.g., "generate_seo_title"')
                                                    ->helperText('Internal identifier (no spaces)')
                                                    ->columnSpan(1),

                                                Select::make('context')
                                                    ->label('Available For')
                                                    ->options([
                                                        'post'  => 'Posts',
                                                        'page'  => 'Pages',
                                                        'media' => 'Media',
                                                        'all'   => 'All',
                                                    ])
                                                    ->default('all')
                                                    ->helperText('Where this prompt can be used')
                                                    ->columnSpan(1),

                                                CodeEditor::make('prompt')
                                                    ->label('Prompt Template')
                                                    ->required()
                                                    ->helperText('Use placeholders like {title} and {content}')
                                                    ->columnSpanFull(),

                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('max_tokens')
                                                            ->label('Max Tokens')
                                                            ->numeric()
                                                            ->default(150)
                                                            ->minValue(10)
                                                            ->maxValue(4000)
                                                            ->helperText('Maximum response length')
                                                            ->required(),

                                                        TextInput::make('temperature')
                                                            ->label('Temperature')
                                                            ->numeric()
                                                            ->default(0.7)
                                                            ->minValue(0)
                                                            ->maxValue(1)
                                                            ->step(0.1)
                                                            ->helperText('0 = focused, 1 = creative')
                                                            ->required(),

                                                        Toggle::make('enabled')
                                                            ->label('Enabled')
                                                            ->default(true)
                                                            ->inline(false),
                                                    ]),

                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Custom Prompt')
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Untitled Prompt')
                                            ->columnSpanFull()
                                            ->cloneable(),

                                    ]),

                                Section::make('Default Prompts')
                                    ->description('Reset to the built-in prompt templates')
                                    ->schema([
                                        TextEntry::make('defaults_info')
                                            ->label('')
                                            ->state('
                                                **Default Prompts:**
                                                - SEO Title Generator
                                                - SEO Meta Description
                                                - Post Teaser/Excerpt
                                                - Image Alt Text

                                                Click "Load Defaults" below to restore these prompts. This will not remove your custom prompts.
                                            ')
                                            ->columnSpanFull(),

                                        Actions::make([
                                            Action::make('load_defaults')
                                                ->label('Load Default Prompts')
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('info')
                                                ->action(function ($livewire) {
                                                    $this->loadDefaultPrompts($livewire);
                                                }),
                                        ]),
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
                'gpt-5'        => 'GPT-5 (Recommended)',
                'gpt-4o'        => 'GPT-4o',
                'gpt-4o-mini'   => 'GPT-4o Mini (Faster, Cheaper)',
                'gpt-4-turbo'   => 'GPT-4 Turbo',
                'gpt-4'         => 'GPT-4',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
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
            default => ['gpt-5' => 'GPT-5'],
        };
    }

    /**
     * Load default prompts into the settings
     */
    protected function loadDefaultPrompts($livewire): void
    {
        $settings = app(AiSettings::class);
        $defaults = DefaultPrompts::all();

        // Merge defaults with existing custom prompts
        // Keep custom prompts that don't conflict with defaults
        $existingCustom = collect($settings->prompts ?? [])
            ->reject(function ($prompt) use ($defaults) {
                return collect($defaults)->contains('action_key', $prompt['action_key'] ?? null);
            });

        $mergedPrompts = array_merge($defaults, $existingCustom->toArray());

        // Update settings
        $settings->prompts = $mergedPrompts;
        $settings->save();

        // Refresh the form with the updated data
        $livewire->form->fill([
            'prompts' => $mergedPrompts,
        ]);

        Notification::make()
            ->success()
            ->title('Default Prompts Loaded')
            ->body('Default prompts have been added. Your custom prompts were preserved.')
            ->send();
    }
}
