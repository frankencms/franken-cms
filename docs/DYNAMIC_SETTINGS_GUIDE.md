# Dynamic Settings Tabs - User Guide

> **💡 What is this?** The Dynamic Settings system allows you to easily add new settings tabs to your Franken CMS admin panel. Whether you're building a custom package or extending your site's functionality, you can seamlessly integrate your settings without modifying core files.

## Table of Contents

- [Quick Start](#quick-start)
- [Core Concepts](#core-concepts)
- [Step-by-Step Tutorial](#step-by-step-tutorial)
- [Real-World Examples](#real-world-examples)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Advanced Usage](#advanced-usage)

---

## Quick Start

**Want to add a settings tab right now?** Here's the minimal code:

### 1. Create a Settings Class

```php
<?php
// app/Settings/MyCustomSettings.php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MyCustomSettings extends Settings
{
    public string $my_option = 'default value';
    public bool $feature_enabled = true;

    public static function group(): string
    {
        return 'my_custom_settings';
    }
}
```

### 2. Create a Tab Provider

```php
<?php
// app/SettingsTabs/MyCustomSettingsTabProvider.php

namespace App\SettingsTabs;

use App\Settings\MyCustomSettings;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

class MyCustomSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('My Custom Settings')
            ->schema([
                Section::make('My Settings')
                    ->schema([
                        TextInput::make('my_option')
                            ->label('My Option')
                            ->required(),

                        Toggle::make('feature_enabled')
                            ->label('Enable Feature')
                            ->default(true),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return MyCustomSettings::class;
    }

    public function getOrder(): int
    {
        return 100; // Higher numbers appear later
    }

    public function getTabKey(): string
    {
        return 'my-custom-settings';
    }
}
```

### 3. Register Your Tab

```php
<?php
// app/Providers/AppServiceProvider.php

use FrankenCms\Services\SettingsTabService;
use App\SettingsTabs\MyCustomSettingsTabProvider;

public function boot(): void
{
    $settingsTabService = $this->app->make(SettingsTabService::class);
    $settingsTabService->registerTab(new MyCustomSettingsTabProvider());
}
```

### 4. Create Migration

```bash
php artisan make:migration create_my_custom_settings
```

```php
<?php
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('my_custom_settings.my_option', 'default value');
        $this->migrator->add('my_custom_settings.feature_enabled', true);
    }
};
```

**That's it!** Your new tab will appear in the CMS settings panel.

---

## Core Concepts

### Settings Groups

Think of settings groups as **containers** for related settings. Each group:
- Has a unique name (like `my_package_api` or `email_notifications`)
- Gets its own database entries
- Can be managed independently
- Prevents conflicts between different packages

```php
// ✅ Good: Unique, descriptive group names
'my_company_api_settings'
'blog_display_options'
'ecommerce_payment_gateway'

// ❌ Bad: Generic names that might conflict
'settings'
'config'
'options'
```

### Tab Providers

Tab Providers are **blueprints** that tell Franken CMS:
- What your tab should look like (form fields, layout)
- Which settings class to use for data storage
- Where to position the tab in the interface
- How to identify the tab uniquely

Think of them as the **glue** between your settings data and the admin interface.

### The Four Components

Every dynamic settings tab needs these four pieces:

1. **Settings Class** - Defines your data structure
2. **Tab Provider** - Defines your form interface
3. **Migration** - Sets up default values in database
4. **Registration** - Tells Franken CMS about your tab

---

## Step-by-Step Tutorial

Let's build a **complete email notification system** from scratch.

### Step 1: Plan Your Settings

First, decide what settings you need:

```
Email Notifications Tab:
- SMTP Host (text input)
- SMTP Port (number input)
- Username (text input)
- Password (password input)
- Enable Notifications (toggle)
- From Email (email input)
- From Name (text input)
```

### Step 2: Create the Settings Class

```php
<?php
// app/Settings/EmailNotificationSettings.php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailNotificationSettings extends Settings
{
    public string $smtp_host = 'smtp.gmail.com';
    public int $smtp_port = 587;
    public ?string $smtp_username = null;
    public ?string $smtp_password = null;
    public bool $notifications_enabled = false;
    public string $from_email = 'noreply@example.com';
    public string $from_name = 'My Website';

    public static function group(): string
    {
        return 'email_notifications';
    }
}
```

### Step 3: Create the Tab Provider

```php
<?php
// app/SettingsTabs/EmailNotificationTabProvider.php

namespace App\SettingsTabs;

use App\Settings\EmailNotificationSettings;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

class EmailNotificationTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Email Notifications')
            ->schema([
                Section::make('SMTP Configuration')
                    ->description('Configure your email server settings')
                    ->columns(2)
                    ->schema([
                        TextInput::make('smtp_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.gmail.com')
                            ->required(),

                        TextInput::make('smtp_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->default(587)
                            ->required(),

                        TextInput::make('smtp_username')
                            ->label('Username')
                            ->email(),

                        TextInput::make('smtp_password')
                            ->label('Password')
                            ->password(),
                    ]),

                Section::make('Email Settings')
                    ->description('Configure default email sender information')
                    ->columns(2)
                    ->schema([
                        Toggle::make('notifications_enabled')
                            ->label('Enable Email Notifications')
                            ->helperText('Turn on/off all email notifications')
                            ->columnSpanFull(),

                        TextInput::make('from_email')
                            ->label('From Email')
                            ->email()
                            ->required(),

                        TextInput::make('from_name')
                            ->label('From Name')
                            ->placeholder('My Website')
                            ->required(),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return EmailNotificationSettings::class;
    }

    public function getOrder(): int
    {
        return 75; // Between Media (50) and Permalinks (60)
    }

    public function getTabKey(): string
    {
        return 'email-notifications';
    }
}
```

### Step 4: Create Migration

```bash
php artisan make:migration create_email_notification_settings
```

```php
<?php
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email_notifications.smtp_host', 'smtp.gmail.com');
        $this->migrator->add('email_notifications.smtp_port', 587);
        $this->migrator->add('email_notifications.smtp_username', null);
        $this->migrator->add('email_notifications.smtp_password', null);
        $this->migrator->add('email_notifications.notifications_enabled', false);
        $this->migrator->add('email_notifications.from_email', 'noreply@example.com');
        $this->migrator->add('email_notifications.from_name', 'My Website');
    }
};
```

### Step 5: Register the Tab

```php
<?php
// app/Providers/AppServiceProvider.php

use FrankenCms\Services\SettingsTabService;
use App\SettingsTabs\EmailNotificationTabProvider;

public function boot(): void
{
    $settingsTabService = $this->app->make(SettingsTabService::class);
    $settingsTabService->registerTab(new EmailNotificationTabProvider());
}
```

### Step 6: Run Migration

```bash
php artisan migrate
```

### Step 7: Use Your Settings

```php
<?php
// Anywhere in your application

$emailSettings = app(App\Settings\EmailNotificationSettings::class);

if ($emailSettings->notifications_enabled) {
    Mail::send(/* ... */);
}

// Or update settings programmatically
$emailSettings->smtp_host = 'new-host.com';
$emailSettings->save();
```

**🎉 Congratulations!** You now have a fully functional email notifications settings tab.

---

## Real-World Examples

### Example 1: Social Media Integration

```php
class SocialMediaSettings extends Settings
{
    public ?string $facebook_app_id = null;
    public ?string $twitter_api_key = null;
    public ?string $instagram_access_token = null;
    public bool $enable_sharing = true;
    public array $enabled_platforms = ['facebook', 'twitter'];

    public static function group(): string
    {
        return 'social_media';
    }
}
```

### Example 2: SEO Settings

```php
class SeoSettings extends Settings
{
    public ?string $meta_title_template = '{title} | {site_name}';
    public ?string $meta_description = null;
    public ?string $google_analytics_id = null;
    public bool $enable_sitemap = true;
    public array $robots_txt_rules = [
        'User-agent: *',
        'Allow: /',
    ];

    public static function group(): string
    {
        return 'seo_settings';
    }
}
```

### Example 3: E-commerce Settings

```php
class EcommerceSettings extends Settings
{
    public string $currency = 'USD';
    public string $currency_symbol = '$';
    public bool $enable_taxes = false;
    public float $tax_rate = 0.0;
    public bool $enable_inventory_tracking = true;
    public int $low_stock_threshold = 10;

    public static function group(): string
    {
        return 'ecommerce';
    }
}
```

---

## Best Practices

### Naming Conventions

```php
// ✅ Settings Classes: PascalCase + "Settings"
EmailNotificationSettings
SocialMediaSettings
PaymentGatewaySettings

// ✅ Groups: snake_case, descriptive
'email_notifications'
'social_media_integration'
'payment_gateway_config'

// ✅ Tab Keys: kebab-case
'email-notifications'
'social-media'
'payment-gateway'

// ✅ Properties: snake_case
public string $smtp_host;
public bool $notifications_enabled;
```

### Security Considerations

```php
// ✅ Mark sensitive fields as passwords
TextInput::make('api_secret')
    ->password() // Hides input value
    ->required(),

// ✅ Use validation rules
TextInput::make('webhook_url')
    ->url()
    ->required(),

// ✅ Provide helpful constraints
TextInput::make('timeout_seconds')
    ->numeric()
    ->minValue(1)
    ->maxValue(300),
```

### User Experience

```php
// ✅ Group related fields
Section::make('API Configuration')
    ->description('Configure your third-party API settings')
    ->schema([
        // Related fields here
    ]),

// ✅ Provide helpful descriptions
Toggle::make('debug_mode')
    ->label('Enable Debug Mode')
    ->helperText('Only enable this in development environments'),

// ✅ Use appropriate input types
TextInput::make('email')
    ->email()
    ->placeholder('user@example.com'),
```

### Performance

```php
// ✅ Use appropriate column spans
Section::make('Settings')
    ->columns(2) // Use grid layout
    ->schema([
        TextInput::make('field1')->columnSpan(1),
        TextInput::make('field2')->columnSpan(1),
        TextInput::make('full_width')->columnSpanFull(),
    ]),
```

---

## Troubleshooting

### Common Issues

#### "Settings class not found"

**Problem:** Your settings class isn't being loaded.

**Solution:**
1. Check your namespace and file location
2. Run `composer dump-autoload`
3. Verify your class extends `Settings`

#### "Tab not appearing"

**Problem:** Your tab doesn't show up in the admin.

**Solution:**
1. Verify you registered it in a service provider
2. Check that `getTabKey()` returns a unique value
3. Clear Laravel cache: `php artisan cache:clear`

#### "Database errors"

**Problem:** Settings can't be saved/loaded.

**Solution:**
1. Run migrations: `php artisan migrate`
2. Check your group name matches between Settings class and migration
3. Verify settings table exists

#### "Form validation errors"

**Problem:** Form won't submit or shows validation errors.

**Solution:**
1. Check required fields have default values or are nullable
2. Verify field names match property names exactly
3. Add appropriate validation rules

#### "Unable to locate class or view for component [filament-panels::form]"

**Problem:** Error when trying to render the settings page.

**Solution:**
This indicates an issue with the CmsSettings page view template. The view should use:
```blade
<x-filament-panels::page>
    {{ $this->form }}

    <x-filament-actions::modals />
</x-filament-panels::page>
```

The form structure should include proper Filament 4 schema components:
```php
public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            \Filament\Schemas\Components\Form::make([
                Tabs::make('Tabs')
                    ->persistTabInQueryString('settings-tab')
                    ->columnSpanFull()
                    ->tabs($settingsTabService->getRegistry()->getTabs()),
            ])
                ->livewireSubmitHandler('save')
                ->footer([
                    \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('save')
                            ->label(__('filament-spatie-laravel-settings-plugin::pages/settings-page.form.actions.save.label'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ])
        ->statePath('data');
}
```

### Debug Mode

Enable debug logging to troubleshoot issues:

```php
// In your tab provider, add logging
public function getTab(): Tab
{
    \Log::info('Loading tab: ' . $this->getTabKey());

    return Tab::make('My Tab')
        // ... rest of configuration
}
```

---

## Advanced Usage

### Conditional Fields

Show/hide fields based on other field values:

```php
Toggle::make('enable_api')
    ->label('Enable API Integration')
    ->live(), // Required for conditional fields

TextInput::make('api_key')
    ->label('API Key')
    ->visible(fn (Get $get) => $get('enable_api')),
```

### Dynamic Options

Load options dynamically:

```php
Select::make('email_template')
    ->label('Email Template')
    ->options(function () {
        return EmailTemplate::pluck('name', 'id');
    })
    ->searchable(),
```

### Custom Validation

Add custom validation rules:

```php
TextInput::make('webhook_url')
    ->label('Webhook URL')
    ->url()
    ->rules(['required', 'url', new WebhookValidator()]),
```

### Grouped Sections

Organize complex forms:

```php
Tab::make('Advanced Settings')
    ->schema([
        Tabs::make('Sub Tabs')
            ->tabs([
                Tab::make('API Settings')->schema([
                    // API fields
                ]),
                Tab::make('Cache Settings')->schema([
                    // Cache fields
                ]),
            ]),
    ]),
```

### File Uploads

Handle file uploads in settings:

```php
FileUpload::make('logo')
    ->label('Company Logo')
    ->image()
    ->directory('settings/logos')
    ->visibility('public'),
```

### Rich Text Fields

Add WYSIWYG editors:

```php
RichEditor::make('email_signature')
    ->label('Email Signature')
    ->toolbarButtons([
        'bold',
        'italic',
        'link',
    ]),
```

---

## Need Help?

- **📖 Full Technical Reference:** [DYNAMIC_SETTINGS_TABS.md](DYNAMIC_SETTINGS_TABS.md)
- **🐛 Found a bug?** Open an issue on GitHub
- **💬 Questions?** Start a discussion on GitHub

---

**Happy building! 🚀** Your settings tab is now part of a powerful, extensible CMS that grows with your needs.