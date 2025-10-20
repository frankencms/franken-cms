<?php

namespace FrankenCms\Filament\Plugins\RichEditor;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Support\Facades\FilamentAsset;

class EnhancedImagePlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getTipTapPhpExtensions(): array
    {
        // This method should return an array of PHP TipTap extension objects.
        // See: https://github.com/ueberdosis/tiptap-php

        // Return StarterKit and Image extensions for proper HTML rendering
        return [
            new \Tiptap\Extensions\StarterKit,
            new \Tiptap\Nodes\Image,
        ];
    }

    public function getTipTapJsExtensions(): array
    {

        // This method should return an array of URLs to JavaScript files containing
        // TipTap extensions that should be asynchronously loaded into the editor
        // when the plugin is used.

        return [
            FilamentAsset::getScriptSrc('enhanced-image', 'frankencms/franken-cms'),
        ];
    }

    public function getEditorTools(): array
    {
        // This method should return an array of `RichEditorTool` objects, which can then be
        // used in the `toolbarButtons()` of the editor.

        // The `jsHandler()` method allows you to access the TipTap editor instance
        // through `$getEditor()`, and `chain()` any TipTap commands to it.
        // See: https://tiptap.dev/docs/editor/api/commands

        // The `action()` method allows you to run an action (registered in the `getEditorActions()`
        // method) when the toolbar button is clicked. This allows you to open a modal to
        // collect additional information from the user before running a command.

        return [
            EnhancedImageTool::make(),
        ];
    }

    public function getEditorActions(): array
    {
        // This method should return an array of `Action` objects, which can be used by the tools
        // registered in the `getEditorTools()` method. The name of the action should match
        // the name of the tool that uses it.

        // The `runCommands()` method allows you to run TipTap commands on the editor instance.
        // It accepts an array of `EditorCommand` objects that define the command to run,
        // as well as any arguments to pass to the command. You should also pass in the
        // `editorSelection` argument, which is the current selection in the editor
        // to apply the commands to.

        return [
            EnhancedImageAction::make(),
        ];
    }
}
