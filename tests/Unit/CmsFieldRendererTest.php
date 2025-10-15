<?php

use FrankenCms\Services\CmsFieldRenderer;
use FrankenCms\Services\FieldRenderers\BooleanFieldRenderer;
use FrankenCms\Services\FieldRenderers\FileFieldRenderer;
use FrankenCms\Services\FieldRenderers\RepeaterFieldRenderer;
use FrankenCms\Services\FieldRenderers\RichEditorFieldRenderer;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

it('renders text fields correctly', function () {
    $renderer = new CmsFieldRenderer;
    $result = $renderer->render('text', 'Hello World');

    expect($result)->toBe('Hello World');
});

it('renders textarea fields correctly', function () {
    $renderer = new CmsFieldRenderer;
    $result = $renderer->render('textarea', 'Multi line\ntext here');

    expect($result)->toBe('Multi line\ntext here');
});

it('escapes HTML in text fields', function () {
    $renderer = new CmsFieldRenderer;
    $result = $renderer->render('text', '<script>alert("xss")</script>');

    expect($result)->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
});

it('renders rich editor fields as HtmlString', function () {
    $renderer = new CmsFieldRenderer;
    $result = $renderer->render('richEditor', '<p>This is <strong>bold</strong></p>');

    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result->toHtml())->toBe('<p>This is <strong>bold</strong></p>');
});

it('renders repeater fields as Collection', function () {
    $renderer = new CmsFieldRenderer;
    $data = [
        ['title' => 'Item 1'],
        ['title' => 'Item 2'],
    ];

    $result = $renderer->render('repeater', $data);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->first())->toBe(['title' => 'Item 1']);
});

it('renders boolean fields correctly', function () {
    $renderer = new CmsFieldRenderer;

    expect($renderer->render('toggle', true))->toBeTrue()
        ->and($renderer->render('toggle', false))->toBeFalse()
        ->and($renderer->render('toggle', 1))->toBeTrue()
        ->and($renderer->render('toggle', 0))->toBeFalse();
});

it('handles null values gracefully', function () {
    $renderer = new CmsFieldRenderer;

    expect($renderer->render('text', null))->toBe('')
        ->and($renderer->render('richEditor', null)->toHtml())->toBe('')
        ->and($renderer->render('repeater', null))->toBeInstanceOf(Collection::class)
        ->and($renderer->render('repeater', null)->isEmpty())->toBeTrue();
});

it('can register custom renderers', function () {
    $renderer = new CmsFieldRenderer;

    $customRenderer = new class implements \FrankenCms\Contracts\FieldRendererInterface
    {
        public function render(mixed $value): mixed
        {
            return 'custom: ' . $value;
        }
    };

    $renderer->registerRenderer('custom', $customRenderer::class);

    $result = $renderer->render('custom', 'test');

    expect($result)->toBe('custom: test');
});

it('falls back to text renderer for unknown field types', function () {
    $renderer = new CmsFieldRenderer;
    $result = $renderer->render('unknown_type', 'some value');

    expect($result)->toBe('some value');
});

it('gets all registered renderers', function () {
    $renderer = new CmsFieldRenderer;
    $renderers = $renderer->getRenderers();

    expect($renderers)->toBeArray()
        ->and($renderers)->toHaveKey('text')
        ->and($renderers)->toHaveKey('richEditor')
        ->and($renderers)->toHaveKey('repeater')
        ->and($renderers['text'])->toBe(TextFieldRenderer::class);
});
