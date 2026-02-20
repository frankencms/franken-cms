<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Settings\StackSettings;

class StackSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        $group = StackSettings::group();

        return Tab::make('Stacks')
            ->icon('heroicon-o-code-bracket')
            ->statePath($group)
            ->schema([

                Section::make('Custom Code Stacks')
                    ->description('Create custom code stacks that can be injected into your theme templates using Laravel\'s `@stack()` directive. Common uses include analytics scripts, custom CSS/JavaScript, third-party widgets, and meta tags.')
                    ->columnSpanFull()
                    ->schema([

                        Repeater::make('stacks')
                            ->label('')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Label')
                                    ->helperText('Descriptive name for this code block (e.g., "Google Analytics", "Facebook Pixel", "Custom Header Scripts")')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('stack_name')
                                    ->label('Stack Name')
                                    ->helperText('The stack identifier used in your theme (e.g., "head", "analytics", "footer"). Must match the @stack() directive in your theme template.')
                                    ->required()
                                    ->maxLength(100)
                                    ->alphaDash()
                                    ->placeholder('e.g., head, analytics, footer')
                                    ->columnSpan(1),

                                Toggle::make('enabled')
                                    ->label('Enabled')
                                    ->helperText('Toggle to enable/disable this code injection')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(1),

                                CodeEditor::make('code')
                                    ->label('Code')
                                    ->helperText('Paste your tracking script, custom CSS, JavaScript, or any HTML code here. The code will be injected exactly as entered.')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Code Stack')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Untitled Stack')
                            ->columnSpanFull()
                            ->cloneable(),

                    ]),

                Section::make('Usage in Themes')
                    ->description('To use these stacks in your theme templates, add the `@stack()` directive where you want the code injected.')
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('usage_example')
                            ->label('')
                            ->state('
**Example Theme Usage:**

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>My Site</title>

    {{-- Inject code from stacks with stack_name="head" --}}
    @stack(\'head\')
</head>
<body>
    @stack(\'body-start\')

    <main>
        {{ $slot }}
    </main>

    @stack(\'analytics\')
    @stack(\'footer\')
</body>
</html>
```

**Common Stack Names:**
- `head` - Scripts/meta tags in the `<head>` section
- `body-start` - Code right after `<body>` tag
- `analytics` - Analytics and tracking scripts
- `footer` - Scripts before closing `</body>` tag
- `chat-widget` - Chat widgets or support tools
- `custom-css` - Additional CSS styles
                            ')
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    public function getSettingsClass(): string
    {
        return StackSettings::class;
    }

    public function getOrder(): int
    {
        return 60;
    }

    public function getTabKey(): string
    {
        return 'stacks';
    }
}
