<?php

namespace FrankenCms\Filament\Plugins\RichEditor;

use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Icons\Heroicon;

class EnhancedImageTool
{
    public static function make(): RichEditorTool
    {
        return RichEditorTool::make('enhancedImage')
            ->label(__('Enhanced Image'))
            ->action(arguments: '{
                alt: $getEditor().getAttributes(\'image\')?.alt ?? null,
                title: $getEditor().getAttributes(\'image\')?.title ?? null,
                caption: $getEditor().getAttributes(\'image\')?.caption ?? null,
                attribution: $getEditor().getAttributes(\'image\')?.attribution ?? null,
                loading: $getEditor().getAttributes(\'image\')?.loading ?? \'lazy\',
                focal_x: $getEditor().getAttributes(\'image\')?.focal_x ?? 50,
                focal_y: $getEditor().getAttributes(\'image\')?.focal_y ?? 50,
                width: $getEditor().getAttributes(\'image\')?.width ?? null,
                height: $getEditor().getAttributes(\'image\')?.height ?? null,
                src: $getEditor().getAttributes(\'image\')?.src ?? null,
                id: $getEditor().getAttributes(\'image\')?.id ?? null,
                css: $getEditor().getAttributes(\'image\')?.css ?? null
            }')
            ->activeKey('image')
            ->icon(Heroicon::Photo)
            ->iconAlias('franken-cms::components.rich-editor.toolbar.enhanced-image');
    }
}
