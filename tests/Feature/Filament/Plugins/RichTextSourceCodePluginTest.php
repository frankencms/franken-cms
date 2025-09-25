<?php

use Filament\Forms\Components\RichEditor;
use FrankenCms\Filament\Plugins\RichEditor\SourceCodePlugin;

it('can create rich content plugin', function () {
    $plugin = SourceCodePlugin::make();

    expect($plugin)->toBeInstanceOf(SourceCodePlugin::class);
});

it('plugin provides source code editor tool', function () {
    $plugin = SourceCodePlugin::make();
    $tools = $plugin->getEditorTools();

    expect($tools)->toHaveCount(1);
    expect($tools[0]->getName())->toBe('sourceCode');
});

it('plugin provides source code action', function () {
    $plugin = SourceCodePlugin::make();
    $actions = $plugin->getEditorActions();

    expect($actions)->toHaveCount(1);
    expect($actions[0]->getName())->toBe('sourceCode');
    expect($actions[0]->getLabel())->toBe('Edit HTML Source');
});

it('can add plugin to rich editor component', function () {
    $richEditor = RichEditor::make('content');
    $enhancedEditor = SourceCodePlugin::addToRichEditor($richEditor);

    expect($enhancedEditor)->toBeInstanceOf(RichEditor::class);
});
