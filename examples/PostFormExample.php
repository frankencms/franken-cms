<?php

namespace FrankenCms\FrankenCms\Examples;

use Filament\Forms\Components\RichEditor;
use FrankenCms\FrankenCms\Filament\Forms\Components\EnhancedImageTool;

class PostFormExample
{
    public static function getEnhancedRichEditor(): RichEditor
    {
        return RichEditor::make('post_content')
            ->live()
            ->json()
            ->tools([
                // Add the enhanced image tool to the available tools
                EnhancedImageTool::make(),
            ])
            ->toolbarButtons([
                // Text Formatting
                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'small', 'lead', 'highlight', 'textColor'],

                // Headings & Alignment
                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],

                // Lists & Structure
                ['bulletList', 'orderedList', 'blockquote', 'codeBlock', 'horizontalRule'],

                // Advanced Elements
                ['link', 'table', 'enhancedImage', 'details'], // Replace 'attachFiles' with 'enhancedImage'

                // Layout & Grid
                ['grid', 'gridDelete'],

                // Merge Tags (if using)
                ['mergeTags'],

                // Actions
                ['undo', 'redo', 'clearFormatting', 'sourceCode'],
            ])
            ->floatingToolbars([
                'paragraph' => [
                    'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'small', 'lead', 'textColor',
                ],
                'heading' => [
                    'h1', 'h2', 'h3',
                ],
                'table' => [
                    'bold', 'italic', 'underline', 'strike',
                ],
                'enhancedImage' => [
                    'enhancedImage', // Allow editing enhanced images via floating toolbar
                ],
            ]);
    }
}