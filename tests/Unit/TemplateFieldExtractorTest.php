<?php

use FrankenCms\Services\TemplateFieldExtractor;

it('parses simple franken directive', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('hero_title')";
    $fields = $parser->parseContent($content);

    expect($fields)->toHaveKey('hero_title')
        ->and($fields['hero_title']['name'])->toBe('hero_title')
        ->and($fields['hero_title']['type'])->toBe('text')
        ->and($fields['hero_title']['options'])->toBe([]);
});

it('parses franken directive with options', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('hero_title', ['label' => 'Hero Title', 'required' => true])";
    $fields = $parser->parseContent($content);

    expect($fields)->toHaveKey('hero_title')
        ->and($fields['hero_title']['options'])->toHaveKey('label')
        ->and($fields['hero_title']['options']['label'])->toBe('Hero Title')
        ->and($fields['hero_title']['options']['required'])->toBeTrue();
});

it('parses multiple franken directives', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = <<<'BLADE'
    <div>
        <h1>@frankenText('title', ['label' => 'Title'])</h1>
        <p>@frankenTextarea('subtitle', ['label' => 'Subtitle'])</p>
        <img src="@frankenFile('image')" />
    </div>
    BLADE;

    $fields = $parser->parseContent($content);

    expect($fields)->toHaveCount(3)
        ->and($fields)->toHaveKeys(['title', 'subtitle', 'image']);
});

it('supports dot notation in field names', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenText('hero.title')";
    $fields = $parser->parseContent($content);

    expect($fields)->toHaveKey('hero.title')
        ->and($fields['hero.title']['name'])->toBe('hero.title');
});

it('throws exception for duplicate field names', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = <<<'BLADE'
    @frankenText('title')
    @frankenTextarea('title')
    BLADE;

    $parser->parseContent($content);
})->throws(RuntimeException::class, "Duplicate field name 'title' found in template");

it('groups fields by section using dot notation', function () {
    $parser = app(TemplateFieldExtractor::class);

    $fields = [
        'hero.title'    => ['name' => 'hero.title', 'type' => 'text', 'options' => []],
        'hero.subtitle' => ['name' => 'hero.subtitle', 'type' => 'textarea', 'options' => []],
        'cta.button'    => ['name' => 'cta.button', 'type' => 'text', 'options' => []],
        'standalone'    => ['name' => 'standalone', 'type' => 'text', 'options' => []],
    ];

    $sections = $parser->getFieldsBySection($fields);

    expect($sections)->toHaveKey('hero')
        ->and($sections)->toHaveKey('cta')
        ->and($sections)->toHaveKey('general')
        ->and($sections['hero'])->toHaveCount(2)
        ->and($sections['cta'])->toHaveCount(1)
        ->and($sections['general'])->toHaveCount(1);
});

it('validates fields correctly', function () {
    $parser = app(TemplateFieldExtractor::class);

    $validFields = [
        'title' => ['name' => 'title', 'type' => 'text', 'options' => []],
    ];

    $invalidFields = [
        'title' => ['name' => '', 'type' => 'text', 'options' => []],
    ];

    expect($parser->validateFields($validFields))->toBeTrue()
        ->and($parser->validateFields($invalidFields))->toBeFalse();
});

it('handles empty template content', function () {
    $parser = app(TemplateFieldExtractor::class);

    $fields = $parser->parseContent('');

    expect($fields)->toBe([]);
});

it('handles template with no franken directives', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = <<<'BLADE'
    <div>
        <h1>Static Title</h1>
        <p>Static content</p>
    </div>
    BLADE;

    $fields = $parser->parseContent($content);

    expect($fields)->toBe([]);
});

it('parses fields with complex options', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = "@frankenSelect('status', ['options' => ['draft' => 'Draft', 'published' => 'Published'], 'default' => 'draft'])";
    $fields = $parser->parseContent($content);

    expect($fields['status']['options'])->toHaveKey('options')
        ->and($fields['status']['options']['options'])->toBeArray()
        ->and($fields['status']['options']['default'])->toBe('draft');
});

it('correctly identifies field types from directive names', function () {
    $parser = app(TemplateFieldExtractor::class);

    $content = <<<'BLADE'
    @frankenText('text_field')
    @frankenTextarea('textarea_field')
    @frankenSelect('select_field')
    @frankenRichEditor('rich_field')
    @frankenToggle('toggle_field')
    BLADE;

    $fields = $parser->parseContent($content);

    expect($fields['text_field']['type'])->toBe('text')
        ->and($fields['textarea_field']['type'])->toBe('textarea')
        ->and($fields['select_field']['type'])->toBe('select')
        ->and($fields['rich_field']['type'])->toBe('richEditor')
        ->and($fields['toggle_field']['type'])->toBe('toggle');
});
