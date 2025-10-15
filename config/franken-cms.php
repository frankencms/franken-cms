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
| CMS Field Mappings
|--------------------------------------------------------------------------
|
| Map your CMS field types to the corresponding FilamentPHP Form Field
| classes. This way, when you define a field in your Blade template, you
| can simply refer to the field type (like "text") and this mapping will
| resolve it to the proper form field class.
|
*/

    'cms_fields' => [
        'text'       => \Filament\Forms\Components\TextInput::class,
        'textarea'   => \Filament\Forms\Components\Textarea::class,
        'email'      => \Filament\Forms\Components\TextInput::class,
        'url'        => \Filament\Forms\Components\TextInput::class,
        'number'     => \Filament\Forms\Components\TextInput::class,
        'select'     => \Filament\Forms\Components\Select::class,
        'checkbox'   => \Filament\Forms\Components\Checkbox::class,
        'toggle'     => \Filament\Forms\Components\Toggle::class,
        'radio'      => \Filament\Forms\Components\Radio::class,
        'file'       => \Filament\Forms\Components\FileUpload::class,
        'image'      => \Filament\Forms\Components\FileUpload::class,
        'repeater'   => \Filament\Forms\Components\Repeater::class,
        'richEditor' => \Filament\Forms\Components\RichEditor::class,
        'datePicker' => \Filament\Forms\Components\DatePicker::class,
        'dateTimePicker' => \Filament\Forms\Components\DateTimePicker::class,
    ],

    /*
|--------------------------------------------------------------------------
| Prism Enable Prompts
|--------------------------------------------------------------------------
*/

    'prism' => [],

];
