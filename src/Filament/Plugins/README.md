# RichText Source Code Plugin

A Filament plugin for FrankenCMS that adds a "Source Code" button to RichEditor components, allowing you to view and edit the HTML source directly.

## Usage

### Basic Usage

To add the source code functionality to a RichEditor component:

```php
use Filament\Forms\Components\RichEditor;
use FrankenCms\Filament\Plugins\RichTextSourceCodePlugin;

// In your form schema
RichTextSourceCodePlugin::addToRichEditor(
    RichEditor::make('content')
        ->label('Content')
        ->required()
)

// Or manually configure the plugin
RichEditor::make('content')
    ->label('Content')
    ->toolbarButtons([
        'bold', 'italic', 'link',
        'bulletList', 'orderedList',
        'h2', 'h3',
        'sourceCode', // Add this for the source code button
    ])
    ->plugins([
        RichTextSourceCodePlugin::make(),
    ])
```

### Example in a Filament Resource

```php
<?php

namespace App\Filament\Resources;

use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use FrankenCms\Filament\Plugins\RichTextSourceCodePlugin;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required(),

            RichTextSourceCodePlugin::addToRichEditor(
                Forms\Components\RichEditor::make('content')
                    ->label('Content')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                    ])
            ),
        ]);
    }
}
```

## Features

- **Source Code Modal**: Click the "Source Code" button to open a modal with the HTML source
- **Monospace Font**: The textarea uses a monospace font for better code readability
- **No Database Updates**: The plugin only updates the component state, not the database
- **Seamless Integration**: Works with any existing RichEditor configuration

## How it Works

1. The plugin adds a toolbar button to the RichEditor component
2. When clicked, it opens a modal with a textarea containing the current HTML content
3. You can edit the HTML source directly
4. Clicking "Update" applies the changes to the RichEditor without saving to the database
5. The changes are only saved when the parent form is submitted

## Customization

You can customize the plugin by extending the `RichTextSourceCodePlugin` class and overriding the methods as needed.