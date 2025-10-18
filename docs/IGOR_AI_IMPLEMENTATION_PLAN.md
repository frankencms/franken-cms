# Igor AI Assistant - Implementation Plan

> **Branding Note**: Internally use "AI" in code/classes. Externally use "Igor" in all user-facing UI, labels, and documentation to match the Frankenstein theme.

## Overview

Implement AI-powered content generation features in FrankenCMS using Prism PHP as an optional dependency. Features will be branded as "Igor" (Frankenstein's assistant) in the UI.

## Prerequisites

- `composer require prism-php/prism` (optional dependency)
- User configures AI provider (OpenAI, Anthropic, Ollama, etc.)
- API key for chosen provider

---

## 📋 Implementation Phases

### **Phase 1: Settings Infrastructure**

**1.1 Settings Class**

Create `AiSettings.php` with nested structure:

```php
namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    // Provider Configuration
    public bool $enabled = false;
    public string $provider = 'openai'; // openai, anthropic, ollama, etc.
    public ?string $api_key = null; // Encrypted
    public string $model = 'gpt-4o';

    // Prompt Templates
    public array $prompts = [];

    public static function group(): string
    {
        return 'cms_ai';
    }

    /**
     * Get casts for encrypted fields
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
        ];
    }
}
```

**1.2 Settings Tab with Nested Tabs**

Create `AiSettingsTabProvider.php`:

```php
Tab::make('Igor') // User-facing: "Igor"
    ->icon('heroicon-o-sparkles')
    ->schema([
        Tabs::make('ai-settings')
            ->tabs([
                // Tab 1: Provider Configuration
                Tab::make('Provider')
                    ->schema([/* provider config */]),

                // Tab 2: Prompt Templates
                Tab::make('Prompts')
                    ->schema([/* prompt management */]),
            ])
    ]);
```

**1.3 Provider Tab Schema**

```php
Section::make('Igor Configuration') // User-facing
    ->description('Configure Igor, your AI assistant, to help generate content.')
    ->schema([
        Toggle::make('enabled')
            ->label('Enable Igor')
            ->helperText('Turn on AI-powered content generation features'),

        Select::make('provider')
            ->label('AI Provider')
            ->options([
                'openai' => 'OpenAI (GPT-4, GPT-4o)',
                'anthropic' => 'Anthropic (Claude)',
                'ollama' => 'Ollama (Local)',
                // More providers
            ])
            ->required()
            ->live(),

        TextInput::make('api_key')
            ->label('API Key')
            ->password()
            ->revealable()
            ->helperText('Your API key will be encrypted and stored securely')
            ->required()
            ->visible(fn ($get) => $get('provider') !== 'ollama'),

        Select::make('model')
            ->label('Model')
            ->options(fn ($get) => $this->getModelsForProvider($get('provider')))
            ->required()
            ->helperText('Choose the AI model to use for generation'),

        Actions::make([
            Action::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-bolt')
                ->action(fn () => $this->testConnection())
        ]),

        Placeholder::make('setup_instructions')
            ->label('Setup Instructions')
            ->content('
                **OpenAI**: Get your API key at https://platform.openai.com/api-keys
                **Anthropic**: Get your API key at https://console.anthropic.com/
                **Ollama**: Install locally at https://ollama.ai
            '),
    ]),
```

**1.4 Prompts Tab Schema**

```php
Section::make('Igor\'s Instructions') // User-facing
    ->description('Customize how Igor generates content. Edit prompts or add your own.')
    ->schema([

        Placeholder::make('help')
            ->content('
                **Available Placeholders:**
                - `{title}` - Post/page title
                - `{content}` - Post/page content
                - `{excerpt}` - Current excerpt
                - `{filename}` - Image filename (for alt text)

                **Tips:**
                - Be specific in your instructions
                - Include desired length/format
                - Adjust temperature for creativity (0 = focused, 1 = creative)
            '),

        Repeater::make('prompts')
            ->label('Prompt Templates')
            ->schema([
                TextInput::make('label')
                    ->label('Label')
                    ->required()
                    ->placeholder('e.g., "SEO Title Generator"'),

                TextInput::make('action_key')
                    ->label('Action Key')
                    ->required()
                    ->alphaDash()
                    ->placeholder('e.g., "generate_seo_title"')
                    ->helperText('Internal identifier (no spaces)'),

                Select::make('context')
                    ->label('Available For')
                    ->options([
                        'post' => 'Posts',
                        'page' => 'Pages',
                        'media' => 'Media',
                        'all' => 'All',
                    ])
                    ->default('all'),

                CodeEditor::make('prompt')
                    ->label('Prompt Template')
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('Generate an SEO title for:\n\nTitle: {title}\nContent: {content}'),

                Grid::make(3)
                    ->schema([
                        TextInput::make('max_tokens')
                            ->label('Max Tokens')
                            ->numeric()
                            ->default(150)
                            ->minValue(10)
                            ->maxValue(4000),

                        TextInput::make('temperature')
                            ->label('Temperature')
                            ->numeric()
                            ->default(0.7)
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.1),

                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(true),
                    ]),
            ])
            ->defaultItems(0)
            ->addActionLabel('Add Custom Prompt')
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state) => $state['label'] ?? 'Untitled'),

        Actions::make([
            Action::make('reset_defaults')
                ->label('Reset to Default Prompts')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->resetToDefaults()),
        ]),
    ]),
```

**1.5 Migration**

```php
// 22_create_ai_settings.php
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_ai.enabled', false);
        $this->migrator->add('cms_ai.provider', 'openai');
        $this->migrator->add('cms_ai.api_key', null);
        $this->migrator->add('cms_ai.model', 'gpt-4o');
        $this->migrator->add('cms_ai.prompts', []);
    }
};
```

---

### **Phase 2: Core Services**

**2.1 Feature Detector**

```php
// src/Services/AiFeatureDetector.php
namespace FrankenCms\Services;

class AiFeatureDetector
{
    public static function isAvailable(): bool
    {
        // Check if Prism is installed
        if (!interface_exists(\EchoLabs\Prism\Contracts\Provider::class)) {
            return false;
        }

        // Check if enabled in settings
        try {
            $settings = app(\FrankenCms\Settings\AiSettings::class);
            return $settings->enabled && !empty($settings->api_key);
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

**2.2 AI Service**

```php
// src/Services/AiService.php
namespace FrankenCms\Services;

use EchoLabs\Prism\Facades\Prism;

class AiService
{
    public function __construct(
        protected PromptManager $promptManager
    ) {}

    /**
     * Generate content using AI
     */
    public function generate(string $actionKey, array $context): string
    {
        if (!AiFeatureDetector::isAvailable()) {
            throw new \Exception('AI features not available');
        }

        $promptConfig = $this->promptManager->getPrompt($actionKey);
        $formattedPrompt = $this->formatPrompt($promptConfig['prompt'], $context);

        $settings = app(\FrankenCms\Settings\AiSettings::class);

        $response = Prism::text()
            ->using($settings->provider, $settings->model)
            ->withPrompt($formattedPrompt)
            ->withMaxTokens($promptConfig['max_tokens'] ?? 500)
            ->withTemperature($promptConfig['temperature'] ?? 0.7)
            ->generate();

        return trim($response->text);
    }

    /**
     * Test provider connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Prism::text()
                ->using($settings->provider, $settings->model)
                ->withPrompt('Respond with only the word "OK"')
                ->withMaxTokens(10)
                ->generate();

            return !empty($response->text);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format prompt template with variables
     */
    protected function formatPrompt(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
```

**2.3 Prompt Manager**

```php
// src/Prompts/PromptManager.php
namespace FrankenCms\Prompts;

class PromptManager
{
    /**
     * Get prompt configuration by action key
     */
    public function getPrompt(string $actionKey): array
    {
        $settings = app(\FrankenCms\Settings\AiSettings::class);

        // Check custom prompts first
        $customPrompt = collect($settings->prompts)
            ->firstWhere('action_key', $actionKey);

        if ($customPrompt && $customPrompt['enabled']) {
            return $customPrompt;
        }

        // Fall back to defaults
        return collect(DefaultPrompts::all())
            ->firstWhere('action_key', $actionKey)
            ?? throw new \Exception("Prompt not found: {$actionKey}");
    }

    /**
     * Get all available prompts for a context
     */
    public function getPromptsForContext(string $context): array
    {
        $settings = app(\FrankenCms\Settings\AiSettings::class);
        $allPrompts = array_merge(
            DefaultPrompts::all(),
            $settings->prompts ?? []
        );

        return collect($allPrompts)
            ->filter(fn ($p) => $p['enabled'] ?? false)
            ->filter(fn ($p) => in_array($p['context'] ?? 'all', [$context, 'all']))
            ->values()
            ->toArray();
    }
}
```

**2.4 Default Prompts**

```php
// src/Prompts/DefaultPrompts.php
namespace FrankenCms\Prompts;

class DefaultPrompts
{
    public static function all(): array
    {
        return [
            [
                'label' => 'SEO Title Generator',
                'action_key' => 'generate_seo_title',
                'context' => 'all',
                'prompt' => 'Generate an SEO-optimized title (50-60 characters) for a blog post.

Title: {title}
Content: {content}

Requirements:
- Must be 50-60 characters
- Include target keywords naturally
- Compelling and click-worthy
- Clear and descriptive

Return only the SEO title, nothing else.',
                'max_tokens' => 100,
                'temperature' => 0.7,
                'enabled' => true,
            ],

            [
                'label' => 'SEO Meta Description',
                'action_key' => 'generate_seo_description',
                'context' => 'all',
                'prompt' => 'Generate an SEO meta description (150-160 characters) for:

Title: {title}
Content: {content}

Requirements:
- Must be 150-160 characters
- Summarize main points
- Include call-to-action
- Use active voice

Return only the meta description, nothing else.',
                'max_tokens' => 150,
                'temperature' => 0.7,
                'enabled' => true,
            ],

            [
                'label' => 'Post Teaser/Excerpt',
                'action_key' => 'generate_teaser',
                'context' => 'post',
                'prompt' => 'Create a compelling teaser/excerpt (2-3 sentences, ~150 characters) for this blog post:

{content}

Requirements:
- Hook the reader
- Summarize key value
- Create curiosity
- 2-3 sentences maximum

Return only the teaser.',
                'max_tokens' => 200,
                'temperature' => 0.8,
                'enabled' => true,
            ],

            [
                'label' => 'Image Alt Text',
                'action_key' => 'generate_alt_text',
                'context' => 'media',
                'prompt' => 'Generate descriptive alt text for accessibility based on this context:

Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Maximum 125 characters
- Describe image content
- Provide context
- Be specific and descriptive

Return only the alt text.',
                'max_tokens' => 100,
                'temperature' => 0.5,
                'enabled' => true,
            ],
        ];
    }
}
```

---

### **Phase 3: Modal Component**

**3.1 Livewire Modal Component**

```php
// src/Filament/Components/AiGeneratorModal.php
namespace FrankenCms\Filament\Components;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class AiGeneratorModal extends Component implements HasForms
{
    use InteractsWithForms;

    // Properties
    public string $actionKey;
    public array $context;
    public string $promptLabel;
    public ?string $currentValue = null;
    public ?string $generatedText = null;
    public bool $isGenerating = false;
    public ?string $error = null;

    // Character count
    public int $characterCount = 0;
    public ?int $targetMin = null;
    public ?int $targetMax = null;

    /**
     * Generate content
     */
    public function generate(): void
    {
        $this->isGenerating = true;
        $this->error = null;

        try {
            $aiService = app(\FrankenCms\Services\AiService::class);
            $this->generatedText = $aiService->generate($this->actionKey, $this->context);
            $this->characterCount = mb_strlen($this->generatedText);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * Regenerate with same prompt
     */
    public function regenerate(): void
    {
        $this->generate();
    }

    /**
     * Accept generation and emit to parent
     */
    public function useGeneration(): void
    {
        $this->dispatch('ai-generation-accepted', [
            'value' => $this->generatedText,
        ]);

        $this->dispatch('close-modal', 'ai-generator');
    }

    /**
     * Cancel and close
     */
    public function cancel(): void
    {
        $this->dispatch('close-modal', 'ai-generator');
    }

    /**
     * Get character count color
     */
    public function getCharacterCountColor(): string
    {
        if (!$this->targetMin || !$this->targetMax) {
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

    public function render()
    {
        return view('franken-cms::filament.components.ai-generator-modal');
    }
}
```

**3.2 Modal Blade View**

```blade
{{-- resources/views/filament/components/ai-generator-modal.blade.php --}}
<x-filament::modal
    id="ai-generator"
    width="2xl"
    :heading="'Ask Igor: ' . $promptLabel"
    icon="heroicon-o-sparkles"
>
    <div class="space-y-4">

        {{-- Current Value --}}
        @if($currentValue)
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Current Value
                </label>
                <div class="mt-1 rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-gray-800">
                    {{ $currentValue }}
                </div>
            </div>
        @endif

        {{-- Generated Result --}}
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Igor's Suggestion
            </label>
            <div class="mt-1 min-h-[100px] rounded-lg border-2 border-dashed border-gray-300 bg-white px-3 py-2 dark:border-gray-600 dark:bg-gray-900">
                @if($isGenerating)
                    <div class="flex items-center justify-center py-8">
                        <x-filament::loading-indicator class="h-6 w-6" />
                        <span class="ml-2 text-sm text-gray-500">Igor is thinking...</span>
                    </div>
                @elseif($generatedText)
                    <p class="text-sm">{{ $generatedText }}</p>
                @else
                    <p class="py-8 text-center text-sm text-gray-400">
                        Click "Generate" to let Igor create content for you
                    </p>
                @endif
            </div>

            {{-- Character Count --}}
            @if($generatedText && $targetMax)
                <div class="mt-1 text-sm" x-bind:class="{
                    'text-gray-500': '{{ $this->getCharacterCountColor() }}' === 'gray',
                    'text-danger-600': '{{ $this->getCharacterCountColor() }}' === 'danger',
                    'text-success-600': '{{ $this->getCharacterCountColor() }}' === 'success',
                    'text-warning-600': '{{ $this->getCharacterCountColor() }}' === 'warning',
                }">
                    {{ $characterCount }} / {{ $targetMax }} characters
                    @if($characterCount >= $targetMin && $characterCount <= $targetMax)
                        ✓
                    @endif
                </div>
            @endif
        </div>

        {{-- Error Message --}}
        @if($error)
            <x-filament::notification
                color="danger"
                icon="heroicon-o-exclamation-triangle"
            >
                {{ $error }}
            </x-filament::notification>
        @endif

    </div>

    {{-- Actions --}}
    <x-slot name="footerActions">
        @if(!$generatedText)
            <x-filament::button
                wire:click="generate"
                wire:loading.attr="disabled"
                color="primary"
            >
                Generate
            </x-filament::button>
        @else
            <x-filament::button
                wire:click="regenerate"
                wire:loading.attr="disabled"
                color="gray"
            >
                Try Again
            </x-filament::button>

            <x-filament::button
                wire:click="useGeneration"
                color="success"
            >
                Use This
            </x-filament::button>
        @endif

        <x-filament::button
            wire:click="cancel"
            color="gray"
            outlined
        >
            Cancel
        </x-filament::button>
    </x-slot>
</x-filament::modal>
```

---

### **Phase 4: Filament Actions**

**4.1 Base AI Action**

```php
// src/Filament/Actions/BaseAiAction.php
namespace FrankenCms\Filament\Actions;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Set;
use Filament\Forms\Get;

abstract class BaseAiAction extends Action
{
    abstract protected function getActionKey(): string;
    abstract protected function getPromptLabel(): string;
    abstract protected function getContext(Get $get): array;

    protected ?int $targetMin = null;
    protected ?int $targetMax = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ask Igor')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->visible(fn () => \FrankenCms\Services\AiFeatureDetector::isAvailable())
            ->modalHeading(fn () => 'Ask Igor: ' . $this->getPromptLabel())
            ->modalWidth('2xl')
            ->action(function (Set $set, Get $get) {
                // Open modal with Livewire component
                $this->dispatch('open-ai-modal', [
                    'actionKey' => $this->getActionKey(),
                    'promptLabel' => $this->getPromptLabel(),
                    'context' => $this->getContext($get),
                    'currentValue' => $get($this->getName()),
                    'targetMin' => $this->targetMin,
                    'targetMax' => $this->targetMax,
                    'fieldName' => $this->getName(),
                ]);
            });
    }

    public function targetLength(int $min, int $max): static
    {
        $this->targetMin = $min;
        $this->targetMax = $max;

        return $this;
    }
}
```

**4.2 Specific Actions**

```php
// src/Filament/Actions/GenerateSeoTitleAction.php
class GenerateSeoTitleAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_seo_title';
    }

    protected function getPromptLabel(): string
    {
        return 'SEO Title Generator';
    }

    protected function getContext(Get $get): array
    {
        return [
            'title' => $get('post_title') ?? $get('title'),
            'content' => $get('post_content') ?? $get('content') ?? '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetLength(50, 60);
    }
}

// src/Filament/Actions/GenerateSeoDescriptionAction.php
class GenerateSeoDescriptionAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_seo_description';
    }

    protected function getPromptLabel(): string
    {
        return 'SEO Meta Description';
    }

    protected function getContext(Get $get): array
    {
        return [
            'title' => $get('post_title') ?? $get('title'),
            'content' => $get('post_content') ?? $get('content') ?? '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetLength(150, 160);
    }
}

// src/Filament/Actions/GenerateTeaserAction.php
class GenerateTeaserAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_teaser';
    }

    protected function getPromptLabel(): string
    {
        return 'Post Teaser';
    }

    protected function getContext(Get $get): array
    {
        return [
            'content' => $get('post_content') ?? $get('content') ?? '',
        ];
    }
}

// src/Filament/Actions/GenerateAltTextAction.php
class GenerateAltTextAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'generate_alt_text';
    }

    protected function getPromptLabel(): string
    {
        return 'Image Alt Text';
    }

    protected function getContext(Get $get): array
    {
        return [
            'title' => $get('../../post_title') ?? '',
            'content' => $get('../../post_content') ?? '',
            'filename' => $get('file_name') ?? '',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetLength(1, 125);
    }
}
```

**4.3 Integration in Forms**

```php
// In HasSeoFields trait:
use FrankenCms\Filament\Actions\GenerateSeoTitleAction;
use FrankenCms\Filament\Actions\GenerateSeoDescriptionAction;

TextInput::make('seo_title')
    ->label('SEO Title')
    ->suffixAction(GenerateSeoTitleAction::make())

Textarea::make('seo_description')
    ->label('Meta Description')
    ->suffixAction(GenerateSeoDescriptionAction::make())

// In Post resource:
use FrankenCms\Filament\Actions\GenerateTeaserAction;

Textarea::make('post_excerpt')
    ->label('Excerpt')
    ->suffixAction(GenerateTeaserAction::make())

// In media upload form:
use FrankenCms\Filament\Actions\GenerateAltTextAction;

TextInput::make('alt')
    ->label('Alt Text')
    ->suffixAction(GenerateAltTextAction::make())
```

---

### **Phase 5: Service Provider Registration**

Update `FrankenCmsServiceProvider.php`:

```php
public function packageRegistered(): void
{
    // ... existing registrations ...

    // Register AI services (only if Prism installed)
    if (interface_exists(\EchoLabs\Prism\Contracts\Provider::class)) {
        $this->app->singleton(\FrankenCms\Services\AiService::class);
        $this->app->singleton(\FrankenCms\Prompts\PromptManager::class);
    }
}

public function configurePackage(Package $package): void
{
    $package
        ->hasMigrations([
            // ... existing migrations ...
            '22_create_ai_settings',
        ]);
}
```

Update `SettingsTabService.php`:

```php
public function registerDefaultTabs(): void
{
    // ... existing tabs ...

    // Only register AI tab if Prism is installed
    if (\FrankenCms\Services\AiFeatureDetector::isAvailable()) {
        $this->registry->register(new AiSettingsTabProvider());
    }
}
```

---

### **Phase 6: Documentation**

**6.1 User Guide**

Create `docs/IGOR.md`:

```markdown
# Igor - Your AI Assistant

Meet Igor, FrankenCMS's AI-powered assistant! Igor helps you generate content quickly using cutting-edge language models.

## Features

- 🎯 SEO-optimized titles and meta descriptions
- ✍️ Compelling post teasers and excerpts
- ♿ Accessibility-focused alt text for images
- 🎨 Fully customizable prompts
- 🔄 Multiple generations until you're happy
- 🔒 Secure API key storage

## Requirements

Igor requires the Prism PHP package:

```bash
composer require prism-php/prism
```

## Setup

1. Navigate to **CMS Settings → Igor**
2. Click the **Provider** tab
3. Toggle **Enable Igor** to ON
4. Select your AI provider (OpenAI, Anthropic, Ollama)
5. Enter your API key
6. Choose a model
7. Click **Test Connection** to verify

### Provider Setup

**OpenAI**
- Get API key: https://platform.openai.com/api-keys
- Recommended model: `gpt-4o`

**Anthropic**
- Get API key: https://console.anthropic.com/
- Recommended model: `claude-3-5-sonnet-20241022`

**Ollama (Local)**
- Install: https://ollama.ai
- No API key needed
- Run: `ollama pull llama2`

## Using Igor

### Generate SEO Title

1. Edit a post or page
2. Click the sparkle icon (✨) next to the SEO Title field
3. Click **Generate** in the modal
4. Review Igor's suggestion
5. Click **Try Again** for different options
6. Click **Use This** when satisfied

### Generate Meta Description

Same process as SEO Title - click the sparkle icon next to Meta Description field.

### Generate Post Teaser

Click the sparkle icon next to the Excerpt field.

### Generate Alt Text

When uploading images, click the sparkle icon next to Alt Text field.

## Customizing Prompts

1. Navigate to **CMS Settings → Igor → Prompts**
2. Find the prompt you want to customize
3. Click to expand it
4. Edit the **Prompt Template**
5. Adjust **Temperature** (0 = focused, 1 = creative)
6. Adjust **Max Tokens** (length limit)
7. Save settings

### Available Placeholders

- `{title}` - Post/page title
- `{content}` - Full post/page content
- `{excerpt}` - Current excerpt
- `{filename}` - Image filename

### Adding Custom Prompts

1. Click **Add Custom Prompt**
2. Enter a **Label** (what users see)
3. Enter an **Action Key** (internal identifier)
4. Select where it's **Available For**
5. Write your **Prompt Template**
6. Configure parameters
7. Toggle **Enabled** to ON
8. Save settings

## Tips

- Be specific in your prompts
- Include desired format and length
- Test different temperature values
- Use placeholders for context
- Generate multiple times for best results

## Troubleshooting

**Igor doesn't appear**
- Check Prism is installed: `composer show prism-php/prism`
- Verify Igor is enabled in settings
- Ensure API key is configured

**Generation fails**
- Test your connection in settings
- Check API key is valid
- Verify you have API credits/quota
- Check error message for details

**Poor results**
- Adjust temperature (lower = more focused)
- Provide more context in placeholders
- Refine your prompt instructions
- Try different models

---

## Privacy & Security

- API keys are encrypted in the database
- No content is stored by AI providers (check their policies)
- You control what data is sent
- Local models (Ollama) keep everything on your server
```

**6.2 Developer Guide**

Create `docs/IGOR_DEVELOPMENT.md`:

```markdown
# Igor Development Guide

## Architecture

```
Services/
├── AiService.php           - Main AI interface
├── AiFeatureDetector.php   - Check availability
Prompts/
├── PromptManager.php       - Retrieve prompts
├── DefaultPrompts.php      - Built-in prompts
Settings/
└── AiSettings.php          - Configuration
Filament/
├── Components/
│   └── AiGeneratorModal.php - Livewire modal
└── Actions/
    ├── BaseAiAction.php     - Reusable base
    └── Generate*Action.php  - Specific actions
```

## Creating Custom AI Actions

### 1. Create Action Class

```php
namespace YourPackage\Filament\Actions;

use FrankenCms\Filament\Actions\BaseAiAction;

class GenerateCustomAction extends BaseAiAction
{
    protected function getActionKey(): string
    {
        return 'your_custom_action';
    }

    protected function getPromptLabel(): string
    {
        return 'Your Custom Generator';
    }

    protected function getContext(Get $get): array
    {
        return [
            'title' => $get('title'),
            'custom_field' => $get('custom_field'),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Optional: set character target
        $this->targetLength(100, 200);
    }
}
```

### 2. Create Default Prompt

```php
// In your service provider
use FrankenCms\Settings\AiSettings;

$settings = app(AiSettings::class);
$settings->prompts[] = [
    'label' => 'Your Custom Generator',
    'action_key' => 'your_custom_action',
    'context' => 'all',
    'prompt' => 'Your prompt template with {title} and {custom_field}',
    'max_tokens' => 200,
    'temperature' => 0.7,
    'enabled' => true,
];
$settings->save();
```

### 3. Add to Form

```php
TextInput::make('your_field')
    ->suffixAction(GenerateCustomAction::make())
```

## Testing

```php
use FrankenCms\Services\AiService;

it('generates content', function () {
    $aiService = app(AiService::class);

    $result = $aiService->generate('generate_seo_title', [
        'title' => 'Test Post',
        'content' => 'Test content...',
    ]);

    expect($result)->toBeString();
    expect(strlen($result))->toBeBetween(50, 60);
});
```

## Best Practices

1. **Always check availability**
   ```php
   if (AiFeatureDetector::isAvailable()) {
       // AI code
   }
   ```

2. **Handle errors gracefully**
   ```php
   try {
       $result = $aiService->generate(...);
   } catch (\Exception $e) {
       // Show user-friendly message
   }
   ```

3. **Provide context**
   ```php
   // Good
   'content' => $get('post_content')

   // Bad
   'content' => '' // Empty context
   ```

4. **Set appropriate limits**
   ```php
   'max_tokens' => 100,  // Short content
   'temperature' => 0.5, // Focused output
   ```
```

---

## 🎯 Implementation Checklist

### Phase 1: Settings
- [ ] Create `AiSettings.php`
- [ ] Create `AiSettingsTabProvider.php` with nested tabs
- [ ] Create provider configuration schema
- [ ] Create prompts management schema
- [ ] Create migration `22_create_ai_settings.php`
- [ ] Register tab in `SettingsTabService`
- [ ] Test settings UI

### Phase 2: Services
- [ ] Create `AiFeatureDetector.php`
- [ ] Create `AiService.php`
- [ ] Create `PromptManager.php`
- [ ] Create `DefaultPrompts.php`
- [ ] Register services in service provider
- [ ] Test AI service with Prism

### Phase 3: Modal
- [ ] Create `AiGeneratorModal.php` Livewire component
- [ ] Create modal Blade view
- [ ] Implement generate/regenerate logic
- [ ] Implement character counting
- [ ] Implement error handling
- [ ] Test modal interactions

### Phase 4: Actions
- [ ] Create `BaseAiAction.php`
- [ ] Create `GenerateSeoTitleAction.php`
- [ ] Create `GenerateSeoDescriptionAction.php`
- [ ] Create `GenerateTeaserAction.php`
- [ ] Create `GenerateAltTextAction.php`
- [ ] Integrate actions in forms
- [ ] Test all actions

### Phase 5: Integration
- [ ] Register services in provider
- [ ] Add migration to provider
- [ ] Conditionally register AI tab
- [ ] Test with and without Prism installed

### Phase 6: Documentation
- [ ] Create `IGOR.md` user guide
- [ ] Create `IGOR_DEVELOPMENT.md` dev guide
- [ ] Add inline code documentation
- [ ] Test documentation accuracy

---

## 🎨 UI/UX Notes

### Branding
- **Code/Internal**: "AI", "AiService", "ai_settings"
- **User-Facing**: "Igor", "Ask Igor", "Igor's Suggestion"

### Icons
- Sparkle icon (✨): `heroicon-o-sparkles`
- Modal icon: `heroicon-o-sparkles`
- Tab icon: `heroicon-o-sparkles`

### Colors
- Primary action: `color="primary"`
- Success (Use This): `color="success"`
- Regenerate: `color="gray"`
- Test connection: `color="info"`

### Copy Examples
- Button: "Ask Igor"
- Modal heading: "Ask Igor: SEO Title Generator"
- Loading: "Igor is thinking..."
- Empty state: "Click 'Generate' to let Igor create content for you"
- Character count: "52 / 60 characters ✓"

---

## 🔒 Security Considerations

1. **API Key Storage**
   - Encrypted using Laravel's `encrypted` cast
   - Never displayed in plain text
   - Revealable field for verification

2. **User Permissions**
   - Respect existing Filament permissions
   - Only show AI features to users who can edit content

3. **Rate Limiting**
   - Consider adding rate limits to prevent abuse
   - Track API usage per user/tenant

4. **Input Sanitization**
   - Sanitize context before sending to AI
   - Validate generated output before saving

---

## 📊 Future Enhancements

**Phase 7: Advanced Features**
- [ ] Batch generation for multiple posts
- [ ] A/B testing variants
- [ ] SEO scoring with AI feedback
- [ ] Auto-tagging based on content
- [ ] Internal linking suggestions
- [ ] Content outline generator
- [ ] Translation assistance

**Phase 8: Analytics**
- [ ] Track AI usage per user
- [ ] Track acceptance rate of suggestions
- [ ] Cost tracking for API usage
- [ ] Performance metrics

---

## End of Plan

This implementation plan provides a complete, production-ready AI assistant feature for FrankenCMS, branded as "Igor" to match the Frankenstein theme. The modal-based workflow ensures users never lose content, and the flexible prompt system allows infinite customization.
