<?php

declare(strict_types=1);

use FrankenCms\Filament\Plugins\RichEditor\EnhancedImagePlugin;

it('enhanced image plugin exists and is properly configured', function () {
    $plugin = EnhancedImagePlugin::make();

    expect($plugin)->toBeInstanceOf(EnhancedImagePlugin::class);
});

it('enhanced image plugin provides correct JavaScript extensions', function () {
    $plugin = EnhancedImagePlugin::make();
    $jsExtensions = $plugin->getTipTapJsExtensions();

    expect($jsExtensions)->toBeArray()
        ->and($jsExtensions)->not->toBeEmpty();
});

it('enhanced image plugin provides editor tools', function () {
    $plugin = EnhancedImagePlugin::make();
    $tools = $plugin->getEditorTools();

    expect($tools)->toBeArray()
        ->and($tools)->not->toBeEmpty()
        ->and($tools[0])->toBeInstanceOf(\Filament\Forms\Components\RichEditor\RichEditorTool::class);
});

it('enhanced image plugin provides editor actions', function () {
    $plugin = EnhancedImagePlugin::make();
    $actions = $plugin->getEditorActions();

    expect($actions)->toBeArray()
        ->and($actions)->not->toBeEmpty()
        ->and($actions[0])->toBeInstanceOf(\Filament\Actions\Action::class);
});
