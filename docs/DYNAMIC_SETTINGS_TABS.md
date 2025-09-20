# Dynamic Settings Tabs System

The FrankenCMS dynamic settings tabs system allows you to create modular, extensible settings forms that can be dynamically populated by the core CMS and external packages.

## Architecture Overview

The system is built around the concept of **Settings Tab Providers** that implement a standardized interface. Each provider manages:

1. **Settings Class**: A Spatie Laravel Settings class that defines the data structure
2. **Form Schema**: A Filament tab with form components
3. **Metadata**: Tab ordering, naming, and identification

## Core Components

### 1. Interface: `SettingsTabProviderInterface`

```php
interface SettingsTabProviderInterface
{
    public function getTab(): Tab;              // Filament tab configuration
    public function getSettingsClass(): string; // Settings class name
    public function getOrder(): int;            // Tab order (lower = first)
    public function getTabKey(): string;        // Unique identifier
}
```

### 2. Registry: `SettingsTabRegistry`

Manages registration and retrieval of tab providers:
- `register(SettingsTabProviderInterface $provider)` - Register a new tab
- `getProviders()` - Get all providers ordered by priority
- `getTabs()` - Get Filament tab components
- `getSettingsClasses()` - Get all settings class names

### 3. Service: `SettingsTabService`

High-level service for managing tabs:
- `registerDefaultTabs()` - Register core CMS tabs
- `registerTab(SettingsTabProviderInterface $provider)` - Register external tab

## Usage for External Packages

### Step 1: Create Your Settings Class

```php
<?php

namespace YourPackage\Settings;

use Spatie\LaravelSettings\Settings;

class YourPackageSettings extends Settings
{
    public ?string $api_key = null;
    public string $api_endpoint = 'https://api.yourservice.com';
    public bool $enabled = true;

    public static function group(): string
    {
        return 'your_package';  // Unique group name (use your package prefix)
    }
}
```

### Step 2: Create Your Tab Provider

```php
<?php

namespace YourPackage\SettingsTabs;

use FrankenCms\Contracts\SettingsTabProviderInterface;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class YourPackageSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Your Package')
            ->schema([
                TextInput::make('api_key')
                    ->label('API Key')
                    ->required(),

                TextInput::make('api_endpoint')
                    ->label('API Endpoint')
                    ->url()
                    ->required(),

                Toggle::make('enabled')
                    ->label('Enable Integration')
                    ->default(true),
            ]);
    }

    public function getSettingsClass(): string
    {
        return YourPackageSettings::class;
    }

    public function getOrder(): int
    {
        return 100; // Higher numbers appear later
    }

    public function getTabKey(): string
    {
        return 'your-package';
    }
}
```

### Step 3: Register Your Tab Provider

In your package's service provider:

```php
<?php

namespace YourPackage;

use Illuminate\Support\ServiceProvider;
use FrankenCms\Services\SettingsTabService;
use YourPackage\SettingsTabs\YourPackageSettingsTabProvider;

class YourPackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your settings tab
        $settingsTabService = $this->app->make(SettingsTabService::class);
        $settingsTabService->registerTab(new YourPackageSettingsTabProvider());
    }
}
```

### Step 4: Create Migration

Create a settings migration for your package:

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('your_package.api_key', null);
        $this->migrator->add('your_package.api_endpoint', 'https://api.yourservice.com');
        $this->migrator->add('your_package.enabled', true);
    }
};
```

## Compatibility with Spatie Laravel Settings

### Multiple Settings Classes

- Each tab provider should use its own unique settings class
- Each settings class should have a unique `group()` name
- Settings are automatically discovered and registered

### Data Storage

- Each settings group gets its own rows in the `settings` table
- Form submission automatically saves to the correct settings class
- Enum handling is built-in with proper string value conversion

### Migration Management

- Create migrations using Spatie's `SettingsMigration` class
- Each package manages its own settings migrations
- Default values are set in migrations

## Built-in Tabs

The core CMS includes these default tabs with `cms_` prefixed groups:

1. **General** (`order: 10`, `group: cms_general`) - Site title, timezone, user roles, etc.
2. **Reading** (`order: 20`, `group: cms_reading`) - Homepage settings, posts per page, etc.
3. **Writing** (`order: 30`, `group: cms_writing`) - Writing preferences (placeholder)
4. **Discussion** (`order: 40`, `group: cms_discussion`) - Comment settings (placeholder)
5. **Media** (`order: 50`, `group: cms_media`) - Image size settings
6. **Permalinks** (`order: 60`, `group: cms_permalinks`) - URL structure settings
7. **Privacy** (`order: 70`, `group: cms_privacy`) - Privacy settings (placeholder)

## Best Practices

### Tab Ordering
- Use multiples of 10 for core tabs (10, 20, 30...)
- Use numbers between multiples for package tabs (15, 25, 35...)
- Higher numbers appear later in the tab order

### Naming Conventions
- Tab keys should be kebab-case: `your-package`
- Settings groups should be snake_case with your package prefix: `your_package_settings` or `mycompany_integration`
- Settings class names should be PascalCase: `YourPackageSettings`
- **Important**: Use unique group prefixes to prevent conflicts with other packages

### Form Components
- Use `->inlineLabel()` for consistency with core tabs
- Include helpful descriptions with `->helperText()`
- Group related fields using `Section` components
- Use appropriate column spans for layout

### Enum Handling
- When using enums in forms, use the built-in enum options helper
- Ensure enums implement `HasLabel` interface
- Map enum values to string keys for spatie/laravel-settings compatibility

```php
// In your tab provider
private function enumOptions(string $enumClass): array
{
    return collect($enumClass::cases())->mapWithKeys(
        fn($case) => [$case->value => $case->getLabel()]
    )->toArray();
}

// Usage in form
Select::make('your_enum_field')
    ->options($this->enumOptions(YourEnum::class))
```

## Benefits

1. **Modularity**: Each package manages its own settings independently
2. **Extensibility**: Easy to add new tabs without modifying core code
3. **Consistency**: Unified settings interface across all packages
4. **Maintainability**: Clear separation of concerns
5. **Type Safety**: Full support for typed settings classes
6. **Flexibility**: Support for complex form layouts and validation

This system makes FrankenCMS highly extensible while maintaining a clean, organized settings interface.