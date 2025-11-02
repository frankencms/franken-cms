<?php

// config for Franken CMS/FrankenCms
use Filament\Forms\Components\RichEditor\TextColor;

return [

    'navigation_group_name' => 'Franken CMS',

    // The folder where your theme is stored in the resources/views directory
    'theme_folder' => 'theme',

    'models' => [
        'user' => \App\Models\User::class,

    ],

    'media_disk_name' => 'public',

    'settings' => [

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

    /*
|--------------------------------------------------------------------------
| Prism Enable Prompts
|--------------------------------------------------------------------------
*/

    'prism' => [],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure available AI providers and their models. Users can customize
    | this list to add new providers or modify model options.
    |
    */

    'ai_providers' => [
        'openai' => [
            'label'  => 'OpenAI (GPT-5)',
            'models' => [
                'gpt-5-chat-latest' => 'GPT-5 (Recommended)',
                'gpt-4o'            => 'GPT-4o',
                'gpt-4o-mini'       => 'GPT-4o Mini (Faster, Cheaper)',
                'gpt-4-turbo'       => 'GPT-4 Turbo',
                'gpt-4'             => 'GPT-4',
                'gpt-3.5-turbo'     => 'GPT-3.5 Turbo',
            ],
        ],
        'anthropic' => [
            'label'  => 'Anthropic (Claude)',
            'models' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Recommended)',
                'claude-3-opus-20240229'     => 'Claude 3 Opus',
                'claude-3-sonnet-20240229'   => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307'    => 'Claude 3 Haiku',
            ],
        ],
    ],

];
