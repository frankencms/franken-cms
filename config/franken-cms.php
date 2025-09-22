<?php

// config for Franken CMS/FrankenCms
return [

    'navigation_group_name' => 'Franken CMS',

    // The folder where your templates are stored in the resources/views directory
    'template_folder' => 'templates',

    'models' => [
        'user' => \App\Models\User::class,

    ],

    'media_disk' => 'public',

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
        //        'TextInput'    => \Filament\Forms\Components\TextInput::class,
        //        'Textarea'     => \Filament\Forms\Components\Textarea::class,
        //        'Select'       => \Filament\Forms\Components\Select::class,
        //        'Checkbox'     => \Filament\Forms\Components\Checkbox::class,
        //        'DatePicker'   => \Filament\Forms\Components\DatePicker::class,
        // Add other mappings as needed...

    ],

];
