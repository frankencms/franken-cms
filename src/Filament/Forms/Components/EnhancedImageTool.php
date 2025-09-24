<?php

namespace FrankenCms\Filament\Forms\Components;

use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Icons\Heroicon;

class EnhancedImageTool
{
    public static function make(): RichEditorTool
    {
        return RichEditorTool::make('enhancedImage')
            ->label(__('Enhanced Image'))
            ->action(arguments: '{
                alt: $getEditor().getAttributes(\'enhancedImage\')?.alt ?? null,
                title: $getEditor().getAttributes(\'enhancedImage\')?.title ?? null,
                caption: $getEditor().getAttributes(\'enhancedImage\')?.caption ?? null,
                attribution: $getEditor().getAttributes(\'enhancedImage\')?.attribution ?? null,
                loading: $getEditor().getAttributes(\'enhancedImage\')?.loading ?? \'lazy\',
                focal_x: $getEditor().getAttributes(\'enhancedImage\')?.focal_x ?? 50,
                focal_y: $getEditor().getAttributes(\'enhancedImage\')?.focal_y ?? 50,
                width: $getEditor().getAttributes(\'enhancedImage\')?.width ?? null,
                height: $getEditor().getAttributes(\'enhancedImage\')?.height ?? null,
                src: $getEditor().getAttributes(\'enhancedImage\')?.src ?? null,
                id: $getEditor().getAttributes(\'enhancedImage\')?.id ?? null
            }')
            ->activeKey('enhancedImage')
            ->icon(Heroicon::Photo)
            ->iconAlias('forms:components.rich-editor.toolbar.enhanced-image');
    }
}
