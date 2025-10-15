<?php

use FrankenCms\Services\TemplateFieldParser;

it('parses simple cmsField directive', function () {
    $parser = new TemplateFieldParser;

    $content = "@cmsField('hero_title', 'text')";
    $fields = $parser->parseContent($content);

    expect($fields)->toHaveKey('hero_title')
        ->and($fields['hero_title']['name'])->toBe('hero_title')
        ->and($fields['hero_title']['type'])->toBe('text')
        ->and($fields['hero_title']['options'])->toBe([]);
});

it('parses cmsField directive with options', function () {
    $parser = new TemplateFieldParser;

    $content = "@cmsField('hero_title', 'text', ['label' => 'Hero Title', 'required' => true])";
    $fields = $parser->parseContent($content);

    expect($fields)->toHaveKey('hero_title')
        ->and($fields['hero_title']['options'])->toHaveKey('label')
        ->and($fields['hero_title']['options']['label'])->toBe('Hero Title')
        ->and($fields['hero_title']['options']['required'])->toBeTrue();
});

it('parses multiple cmsField directives', function () {
    $parser = new TemplateFieldParser;

    $content = <<<'BLADE'
    <div>
        <h1>@cmsField('title', 'text', ['label' => 'Title'])</h1>
        <p>@cmsField('subtitle', 'textarea', ['label' => 'Subtitle'])</p>
        <img src="@cmsField('image', 'file')" />
    </div>
    BLADE;

    $fields = $parser->parseContent($content);

    expect($fields)->toHaveCount(3)
        ->and($fields)->toHaveKeys(['title', 'subtitle', 'image']);
});

it('supports dot notation in field names', function () {
    $parser = new TemplateFieldParser;

    $content = "@cmsField('hero.title', 'text')";
    $fields = $parser->parseContent($content);

    expect($fields)->toHaveKey('hero.title')
        ->and($fields['hero.title']['name'])->toBe('hero.title');
});

it('throws exception for duplicate field names', function () {
    $parser = new TemplateFieldParser;

    $content = <<<'BLADE'
    @cmsField('title', 'text')
    @cmsField('title', 'textarea')
    BLADE;

    $parser->parseContent($content);
})->throws(RuntimeException::class, "Duplicate field name 'title' found in template");

it('groups fields by section using dot notation', function () {
    $parser = new TemplateFieldParser;

    $fields = [
        'hero.title' => ['name' => 'hero.title', 'type' => 'text', 'options' => []],
        'hero.subtitle' => ['name' => 'hero.subtitle', 'type' => 'textarea', 'options' => []],
        'cta.button' => ['name' => 'cta.button', 'type' => 'text', 'options' => []],
        'standalone' => ['name' => 'standalone', 'type' => 'text', 'options' => []],
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
    $parser = new TemplateFieldParser;

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
    $parser = new TemplateFieldParser;

    $fields = $parser->parseContent('');

    expect($fields)->toBe([]);
});

it('handles template with no cmsField directives', function () {
    $parser = new TemplateFieldParser;

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
    $parser = new TemplateFieldParser;

    $content = "@cmsField('status', 'select', ['options' => ['draft' => 'Draft', 'published' => 'Published'], 'default' => 'draft'])";
    $fields = $parser->parseContent($content);

    expect($fields['status']['options'])->toHaveKey('options')
        ->and($fields['status']['options']['options'])->toBeArray()
        ->and($fields['status']['options']['default'])->toBe('draft');
});
