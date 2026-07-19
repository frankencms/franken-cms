<?php

use FrankenCms\Contracts\FieldRendererInterface;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use FrankenCms\Services\TemplateFieldRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

it('renders text fields correctly', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $result = $renderer->render('text', 'Hello World');

    expect($result)->toBe('Hello World');
});

it('renders textarea fields correctly', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $result = $renderer->render('textarea', 'Multi line\ntext here');

    expect($result)->toBe('Multi line\ntext here');
});

it('escapes HTML in text fields', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $result = $renderer->render('text', '<script>alert("xss")</script>');

    expect($result)->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
});

it('renders rich editor fields as HtmlString', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $result = $renderer->render('richEditor', '<p>This is <strong>bold</strong></p>');

    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result->toHtml())->toBe('<p>This is <strong>bold</strong></p>');
});

it('renders repeater fields as Collection', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $data = [
        ['title' => 'Item 1'],
        ['title' => 'Item 2'],
    ];

    $result = $renderer->render('repeater', $data);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->first())->toBeInstanceOf(Collection::class)
        ->and($result->first()->get('title'))->toBe('Item 1');
});

it('renders boolean fields correctly', function () {
    $renderer = app(TemplateFieldRenderer::class);

    expect($renderer->render('toggle', true))->toBeTrue()
        ->and($renderer->render('toggle', false))->toBeFalse()
        ->and($renderer->render('toggle', 1))->toBeTrue()
        ->and($renderer->render('toggle', 0))->toBeFalse();
});

it('handles null values gracefully', function () {
    $renderer = app(TemplateFieldRenderer::class);

    expect($renderer->render('text', null))->toBe('')
        ->and($renderer->render('richEditor', null)->toHtml())->toBe('')
        ->and($renderer->render('repeater', null))->toBeInstanceOf(Collection::class)
        ->and($renderer->render('repeater', null)->isEmpty())->toBeTrue();
});

it('can register custom renderers', function () {
    $renderer = app(TemplateFieldRenderer::class);

    $customRenderer = new class implements FieldRendererInterface
    {
        public function render(mixed $value, ?string $fieldName = null): mixed
        {
            return 'custom: ' . $value;
        }
    };

    $renderer->registerRenderer('custom', $customRenderer::class);

    $result = $renderer->render('custom', 'test');

    expect($result)->toBe('custom: test');
});

it('falls back to text renderer for unknown field types', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $result = $renderer->render('unknown_type', 'some value');

    expect($result)->toBe('some value');
});

it('gets all registered renderers', function () {
    $renderer = app(TemplateFieldRenderer::class);
    $renderers = $renderer->getRenderers();

    expect($renderers)->toBeArray()
        ->and($renderers)->toHaveKey('text')
        ->and($renderers)->toHaveKey('richEditor')
        ->and($renderers)->toHaveKey('repeater')
        ->and($renderers['text'])->toBe(TextFieldRenderer::class);
});
