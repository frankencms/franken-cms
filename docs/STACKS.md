# Custom Code Stacks

FrankenCMS provides a powerful and flexible system for injecting custom code into your theme templates using Laravel's native `@stack()` directive. This feature allows you to add analytics scripts, custom CSS/JavaScript, third-party widgets, meta tags, and any other HTML/JavaScript code to specific locations in your theme.

## Overview

Unlike traditional CMS platforms that have hardcoded injection points (like "header scripts" and "footer scripts"), FrankenCMS uses **Laravel Stacks**, giving you complete control over where and how code is injected.

### Key Benefits

- **🎯 Semantic Stack Names**: Use meaningful names like `analytics`, `chat-widget`, or `custom-css` instead of generic positions
- **🎨 Theme-Controlled**: Your theme defines where stacks are rendered, not the CMS
- **♾️ Unlimited Flexibility**: Create as many code blocks as needed, targeting any stack
- **🔄 Reorderable**: Drag and drop to control the order of code injection
- **✅ Toggle-able**: Enable/disable individual code blocks without deleting them
- **⚡ Cached**: Benefits from Spatie Settings caching for optimal performance

## Admin Interface

Navigate to **CMS Settings → Stacks** in your Filament admin panel.

### Creating a Code Stack

Each code stack entry contains:

1. **Label** - A descriptive name for the admin UI (e.g., "Google Analytics", "Facebook Pixel")
2. **Stack Name** - The Laravel stack identifier used in your theme (e.g., `head`, `analytics`, `footer`)
3. **Enabled** - Toggle to enable/disable this code injection
4. **Code** - The actual code/script to inject

### Example Configuration

```
Label: Google Analytics
Stack Name: analytics
Enabled: ✓
Code:
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

## Theme Integration

### Using @stack() in Your Theme

Add `@stack('name')` directives in your theme templates where you want code injected:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>

    {{-- Inject code from stacks with stack_name="head" --}}
    @stack('head')

    {{-- Custom CSS stacks --}}
    @stack('custom-css')
</head>
<body>
    {{-- Code right after <body> tag (e.g., GTM noscript) --}}
    @stack('body-start')

    <header>
        <nav>...</nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer>
        {{-- Chat widgets or support tools --}}
        @stack('chat-widget')
    </footer>

    {{-- Analytics scripts before closing </body> --}}
    @stack('analytics')

    {{-- Other footer scripts --}}
    @stack('footer')
</body>
</html>
```

### Common Stack Names

While you can use any stack name, we recommend these conventions for consistency:

| Stack Name | Purpose | Typical Location |
|------------|---------|------------------|
| `head` | General head scripts/meta tags | In `<head>` section |
| `head-early` | Scripts that must load first | Top of `<head>` |
| `body-start` | Code right after `<body>` | After `<body>` tag |
| `analytics` | Analytics and tracking scripts | Before `</body>` |
| `footer` | General footer scripts | Before `</body>` |
| `chat-widget` | Chat widgets or support tools | In footer area |
| `custom-css` | Additional CSS styles | In `<head>` |
| `custom-js` | Additional JavaScript | Before `</body>` |
| `conversion-tracking` | Conversion/pixel tracking | Before `</body>` |

## Multiple Code Blocks per Stack

You can create multiple code blocks targeting the same stack. They will be rendered in the order they appear in the admin interface (reorderable via drag-and-drop).

**Example:**

```
Code Block 1:
  Label: Google Analytics
  Stack Name: analytics
  Code: <script>/* GA code */</script>

Code Block 2:
  Label: Facebook Pixel
  Stack Name: analytics
  Code: <script>/* FB Pixel code */</script>

Code Block 3:
  Label: Custom Event Tracking
  Stack Name: analytics
  Code: <script>/* Custom tracking */</script>
```

All three will be injected into `@stack('analytics')` in the order shown.

## Technical Implementation

### How It Works

1. **Storage**: Code stacks are stored in the `settings` table using Spatie Laravel Settings
2. **Injection**: A view composer runs on every front-end request and injects enabled stacks
3. **Caching**: Settings are cached using Laravel's cache system (when enabled)
4. **Performance**: The view composer only runs for front-end views, not admin panel

### Settings Class

The `StackSettings` class stores all code stacks:

```php
namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class StackSettings extends Settings
{
    public array $stacks = [];

    public static function group(): string
    {
        return 'cms_stacks';
    }
}
```

### View Composer

Code injection is handled by a view composer in `FrankenCmsServiceProvider`:

```php
View::composer('*', function ($view) {
    $stackSettings = app(\FrankenCms\Settings\StackSettings::class);
    $stacksByName = $stackSettings->getEnabledStacksByName();

    foreach ($stacksByName as $stackName => $codeBlocks) {
        foreach ($codeBlocks as $code) {
            $view->getFactory()->startPush($stackName, $code . PHP_EOL);
        }
    }
});
```

## Performance Optimization

### Settings Caching

For optimal performance in production, enable Spatie Settings caching in your `.env`:

```env
SETTINGS_CACHE_ENABLED=true
```

This caches all settings (including stacks) in Laravel's cache, preventing database queries on every request.

### Cache Configuration

You can configure the cache store and TTL in `config/settings.php`:

```php
'cache' => [
    'enabled' => env('SETTINGS_CACHE_ENABLED', false),
    'store' => 'redis', // Use your preferred cache driver
    'prefix' => 'settings',
    'ttl' => 3600, // Cache for 1 hour
],
```

### Cache Clearing

Spatie Settings automatically clears the cache when settings are saved. No manual cache clearing needed!

## Use Cases

### Analytics & Tracking

```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

### Meta Tags & Schema Markup

```html
<!-- Organization Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Your Company",
  "url": "https://example.com"
}
</script>
```

### Custom CSS

```html
<style>
:root {
  --brand-color: #4F46E5;
  --accent-color: #10B981;
}

.custom-banner {
  background: var(--brand-color);
  padding: 2rem;
}
</style>
```

### Third-Party Widgets

```html
<!-- Chat Widget -->
<script>
  window.intercomSettings = {
    app_id: "YOUR_APP_ID"
  };
</script>
<script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/YOUR_APP_ID';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();</script>
```

### Conversion Tracking

```html
<!-- Facebook Pixel -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', 'YOUR_PIXEL_ID');
fbq('track', 'PageView');
</script>
```

## Best Practices

### 1. Use Semantic Stack Names

❌ **Don't:**
```blade
@stack('position-1')
@stack('scripts-here')
```

✅ **Do:**
```blade
@stack('analytics')
@stack('chat-widget')
```

### 2. Group Related Scripts

Keep related scripts in the same code block for easier management.

### 3. Test in Development

Always test custom code in a development environment before deploying to production.

### 4. Use Comments

Add HTML comments to your code blocks for clarity:

```html
<!-- Google Analytics - Updated 2024-01-15 -->
<script>...</script>
```

### 5. Leverage Enabled Toggle

Use the enabled toggle to temporarily disable code blocks instead of deleting them. This is useful for:
- A/B testing different analytics providers
- Debugging performance issues
- Seasonal promotions or campaigns

### 6. Keep Stack Names Consistent

Document your theme's stack names so content editors know which stack to use for different purposes.

## Troubleshooting

### Code Not Appearing

1. **Check if enabled**: Ensure the code block toggle is enabled
2. **Verify stack name**: Ensure the stack name matches the `@stack()` directive in your theme
3. **Clear cache**: If caching is enabled, clear it: `php artisan cache:clear`
4. **Check theme**: Verify the `@stack()` directive exists in your theme template

### Code Appearing in Admin Panel

The view composer automatically excludes admin panel routes. Code should only appear on front-end views.

### Multiple Instances of Same Code

Check if you have duplicate code blocks targeting the same stack. Reorder or disable duplicates as needed.

## Extending the Feature

### Programmatic Stack Registration

External packages can register stacks programmatically:

```php
use FrankenCms\Settings\StackSettings;

$stackSettings = app(StackSettings::class);
$stacks = $stackSettings->stacks;

$stacks[] = [
    'label' => 'My Package Script',
    'stack_name' => 'head',
    'code' => '<script>console.log("Hello from package!");</script>',
    'enabled' => true,
];

$stackSettings->stacks = $stacks;
$stackSettings->save();
```

### Custom Validation

You can add validation to the `StackSettingsTabProvider` to enforce code standards:

```php
Textarea::make('code')
    ->label('Code')
    ->rules([
        function () {
            return function (string $attribute, $value, Closure $fail) {
                if (!str_contains($value, '<script') && !str_contains($value, '<style')) {
                    $fail('The code must contain HTML, CSS, or JavaScript.');
                }
            };
        },
    ])
```

## Comparison with Traditional CMS

### WordPress Approach
WordPress plugins typically provide a few hardcoded injection points:
- Header scripts
- Footer scripts
- After opening `<body>` tag

**Limitations:**
- Fixed positions only
- Not semantic
- Theme has no control

### FrankenCMS Approach
FrankenCMS uses Laravel Stacks:
- **Theme-controlled**: Theme defines stack locations
- **Unlimited positions**: Create as many stacks as needed
- **Semantic naming**: Clear, meaningful stack names
- **Laravel-native**: Uses built-in Blade functionality

## Conclusion

The Stacks feature provides a modern, flexible, and performant way to inject custom code into your FrankenCMS site. By leveraging Laravel's native stack system, it provides developers with complete control while maintaining a user-friendly admin interface for content editors.

For questions or suggestions, please open an issue on the [FrankenCMS GitHub repository](https://github.com/frankencms/franken-cms).
