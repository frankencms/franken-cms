<?php

namespace FrankenCms\Filament\Plugin;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;

class RichTextSourceCodePlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getTipTapPhpExtensions(): array
    {
        // TODO: Implement getTipTapPhpExtensions() method.
    }

    public function getTipTapJsExtensions(): array
    {
        // TODO: Implement getTipTapJsExtensions() method.
    }

    public function getEditorTools(): array
    {
        // TODO: Implement getEditorTools() method.
    }

    public function getEditorActions(): array
    {
        // TODO: Implement getEditorActions() method.
    }
}
