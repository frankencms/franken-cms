<?php

namespace FrankenCms\Filament\Plugins;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class RichTextSourceCodePlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('sourceCode')
                ->label('Source Code')
                ->icon(Heroicon::CodeBracket)
                ->action(arguments: '{ editorContent: $getEditor().getHTML() }'),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('sourceCode')
                ->label('Edit HTML Source')
                ->modalHeading('HTML Source Code')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitActionLabel('Update')
                ->modalCancelActionLabel('Cancel')
                ->fillForm(function (array $arguments): array {
                    return [
                        'html_content' => $arguments['editorContent'] ?? '',
                    ];
                })
                ->form([
                    Textarea::make('html_content')
                        ->label('HTML Source')
                        ->rows(20)
                        ->columnSpanFull()
                        ->extraAttributes([
                            'style'      => 'font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace; font-size: 12px;',
                            'spellcheck' => 'false',
                        ])
                        ->helperText('Edit the HTML source code and click Update to apply changes.'),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands(
                        [
                            EditorCommand::make('setContent', arguments: [$data['html_content']]),
                        ],
                        editorSelection: $arguments['editorSelection'],
                    );
                }),
        ];
    }

    public static function addToRichEditor(RichEditor $component): RichEditor
    {
        $existingButtons = [];
        try {
            $existingButtons = $component->getToolbarButtons() ?? ['bold', 'italic', 'link'];
        } catch (\Error $e) {
            $existingButtons = ['bold', 'italic', 'link'];
        }

        return $component
            ->toolbarButtons([
                ...$existingButtons,
                'sourceCode',
            ])
            ->plugins([
                self::make(),
            ]);
    }
}