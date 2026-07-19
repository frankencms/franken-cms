<?php

// config for Franken CMS/FrankenCms
use App\Models\User;
use Filament\Forms\Components\RichEditor\TextColor;

return [

    'navigation_group_name' => 'Franken CMS',

    // The folder where your theme is stored in the resources/views directory
    'theme_folder' => 'theme',

    'models' => [
        'user' => User::class,

    ],

    'media_disk_name' => env('MEDIA_DISK', 'public'),

    'settings' => [

    ],

    'ai' => [
        // Ollama has no API key; opt in explicitly to expose it as a provider.
        'enable_ollama' => env('CMS_AI_ENABLE_OLLAMA', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | OG Image Generation
    |--------------------------------------------------------------------------
    |
    | Requires spatie/laravel-og-image (composer require spatie/laravel-og-image).
    | Map post types to Blade views containing an <x-og-image> component; add
    | <x-franken-og-image /> to your theme layout once. Manual per-post uploads
    | and the site default image are used automatically as fallbacks.
    | For hosts without Chrome/Node, set the Cloudflare credentials below to
    | render via Cloudflare's Browser Rendering API.
    |
    */

    'og_image' => [
        'enabled'   => true,
        'templates' => [
            // 'post' => 'theme.og-templates.post',
            // 'page' => 'theme.og-templates.page',
        ],

        // Site-wide fallback template, used when a page has no type template,
        // no manually uploaded image, and no default image in SEO settings.
        'default_template' => null, // e.g. 'theme.og-templates.default'
        'cloudflare'       => [
            'api_token'  => env('CLOUDFLARE_API_TOKEN'),
            'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the menu system including caching,
    | templates, and linkable model types.
    |
    */

    'menu_cache' => 3600, // Cache TTL in seconds (default: 1 hour)

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the breadcrumbs system. Breadcrumbs are
    | automatically generated for pages, posts, and taxonomy archives based
    | on the URL structure.
    |
    */

    'breadcrumbs' => [
        'enabled'      => true,   // Enable/disable breadcrumbs globally
        'home_text'    => 'Home', // Text for the home link
        'show_current' => true,   // Show current page in breadcrumbs
    ],

    /*
    |--------------------------------------------------------------------------
    | User Bio Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the user bio system including profile images.
    |
    */

    'user_bio' => [
        // Profile image shape: 'circle' or 'square'
        'image_shape' => env('CMS_BIO_IMAGE_SHAPE', 'circle'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Platforms Configuration
    |--------------------------------------------------------------------------
    |
    | Define available social media platforms for user profiles. Each platform
    | requires a label, url_pattern (with {username} placeholder), and optionally
    | an icon (Blade Icons component name) and placeholder text.
    |
    | Users can enter either a username or full URL. Usernames are automatically
    | converted to full URLs using the url_pattern.
    |
    | To add custom platforms or override defaults, add entries here.
    | The defaults (Twitter, GitHub, LinkedIn, etc.) are provided by
    | SocialLinksService if this config key is not set.
    |
    | Default icons use Lucide (lucide-*). Install with:
    |   composer require mallardduck/blade-lucide-icons
    |
    | Example custom platform:
    |   'myplatform' => [
    |       'label' => 'My Platform',
    |       'url_pattern' => 'https://myplatform.com/u/{username}',
    |       'icon' => 'lucide-link',  // Any Blade Icons component
    |       'placeholder' => 'username or URL',
    |   ],
    |
    */

    // 'social_platforms' => [
    //     // Uncomment and customize to override or extend default platforms
    // ],

    /*
    |--------------------------------------------------------------------------
    | CMS Field Parsing Cache
    |--------------------------------------------------------------------------
    |
    | Enable in-memory caching of parsed template fields. When enabled,
    | template files are parsed once per request and cached. The cache
    | automatically invalidates when template files are modified.
    |
    | Recommended: true for production, false for local development
    |
    */

    'cache_parsed_fields' => env('CMS_CACHE_PARSED_FIELDS', true),

    'rich_editor' => [
        'custom_text_colors' => [
            'brand'     => TextColor::make(label: 'Brand', color: '#919831'),
            'brand_alt' => TextColor::make(label: 'Brand Alternate', color: '#715B1A', darkColor: '#E59F2F'),
        ],
    ],

];
