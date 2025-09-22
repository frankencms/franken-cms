<?php

use Filament\Forms\Components\RichEditor;
use FrankenCms\Filament\Plugins\RichTextSourceCodePlugin;

it('can create rich content plugin', function () {
    $plugin = RichTextSourceCodePlugin::make();

    expect($plugin)->toBeInstanceOf(RichTextSourceCodePlugin::class);
});

it('plugin provides source code editor tool', function () {
    $plugin = RichTextSourceCodePlugin::make();
    $tools = $plugin->getEditorTools();

    expect($tools)->toHaveCount(1);
    expect($tools[0]->getName())->toBe('sourceCode');
});

it('plugin provides source code action', function () {
    $plugin = RichTextSourceCodePlugin::make();
    $actions = $plugin->getEditorActions();

    expect($actions)->toHaveCount(1);
    expect($actions[0]->getName())->toBe('sourceCode');
    expect($actions[0]->getLabel())->toBe('Edit HTML Source');
});

it('can add plugin to rich editor component', function () {
    $richEditor = RichEditor::make('content');
    $enhancedEditor = RichTextSourceCodePlugin::addToRichEditor($richEditor);

    expect($enhancedEditor)->toBeInstanceOf(RichEditor::class);
});