<?php

use FrankenCms\Models\Page;
use FrankenCms\Services\TemplateFieldExtractor;
use FrankenCms\Services\TemplateFieldRenderer;
use FrankenCms\Tests\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('stores and retrieves custom fields in page', function () {
    $page = Page::factory()->create([
        'post_content' => [
            'custom_fields' => [
                'hero.title'    => 'Welcome to our site',
                'hero.subtitle' => 'We build amazing things',
            ],
        ],
    ]);

    expect($page->custom_fields)->toBeArray()
        ->and($page->custom_fields['hero.title'])->toBe('Welcome to our site')
        ->and($page->custom_fields['hero.subtitle'])->toBe('We build amazing things');
});

it('updates custom fields in page', function () {
    $page = Page::factory()->create([
        'post_content' => [
            'custom_fields' => [
                'title' => 'Old Title',
            ],
        ],
    ]);

    $content = $page->post_content;
    $content['custom_fields'] = [
        'title'    => 'New Title',
        'subtitle' => 'New Subtitle',
    ];
    $page->post_content = $content;
    $page->save();

    $page->refresh();

    expect($page->custom_fields['title'])->toBe('New Title')
        ->and($page->custom_fields['subtitle'])->toBe('New Subtitle');
});

it('renders text field values correctly', function () {
    $renderer = app(TemplateFieldRenderer::class);

    $value = 'Hello World';
    $result = $renderer->render('text', $value);

    expect($result)->toBe('Hello World');
});

it('renders rich editor content correctly', function () {
    $renderer = app(TemplateFieldRenderer::class);

    $value = '<p>Rich <strong>content</strong></p>';
    $result = $renderer->render('richEditor', $value);

    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result->toHtml())->toBe('<p>Rich <strong>content</strong></p>');
});

it('renders repeater data as collection', function () {
    $renderer = app(TemplateFieldRenderer::class);

    $value = [
        ['name' => 'Item 1', 'description' => 'First item'],
        ['name' => 'Item 2', 'description' => 'Second item'],
    ];

    $result = $renderer->render('repeater', $value);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->first()['name'])->toBe('Item 1');
});

it('parses template and identifies custom fields', function () {
    $extractor = app(TemplateFieldExtractor::class);

    $templateContent = <<<'BLADE'
    <div class="hero">
        <h1>@frankenText('hero.title', ['label' => 'Hero Title', 'required' => true])</h1>
        <p>@frankenTextarea('hero.subtitle', ['label' => 'Subtitle'])</p>
        <img src="@frankenFile('hero.image', ['label' => 'Hero Image'])" />
    </div>
    BLADE;

    $fields = $extractor->parseContent($templateContent);

    expect($fields)->toHaveCount(3)
        ->and($fields)->toHaveKey('hero.title')
        ->and($fields)->toHaveKey('hero.subtitle')
        ->and($fields)->toHaveKey('hero.image')
        ->and($fields['hero.title']['type'])->toBe('text')
        ->and($fields['hero.title']['options']['label'])->toBe('Hero Title')
        ->and($fields['hero.title']['options']['required'])->toBeTrue();
});

it('groups template fields by section', function () {
    $extractor = app(TemplateFieldExtractor::class);

    $templateContent = <<<'BLADE'
    @frankenText('hero.title')
    @frankenText('hero.subtitle')
    @frankenText('cta.button_text')
    @frankenText('cta.button_link')
    @frankenText('footer_text')
    BLADE;

    $fields = $extractor->parseContent($templateContent);
    $sections = $extractor->getFieldsBySection($fields);

    expect($sections)->toHaveKey('hero')
        ->and($sections)->toHaveKey('cta')
        ->and($sections)->toHaveKey('general')
        ->and($sections['hero'])->toHaveCount(2)
        ->and($sections['cta'])->toHaveCount(2)
        ->and($sections['general'])->toHaveCount(1);
});

it('handles empty custom fields gracefully', function () {
    $page = Page::factory()->create([
        'post_content' => [],
    ]);

    expect($page->custom_fields)->toBeArray()
        ->and($page->custom_fields)->toBeEmpty();
});

it('preserves other post_content data when setting custom fields', function () {
    $page = Page::factory()->create([
        'post_content' => [
            'other_data' => 'important value',
        ],
    ]);

    $content = $page->post_content;
    $content['custom_fields'] = ['title' => 'Test Title'];
    $page->post_content = $content;
    $page->save();

    $page->refresh();

    expect($page->post_content)->toHaveKey('other_data')
        ->and($page->post_content['other_data'])->toBe('important value')
        ->and($page->post_content)->toHaveKey('custom_fields')
        ->and($page->custom_fields['title'])->toBe('Test Title');
});

it('handles dot notation in custom field keys', function () {
    $page = Page::factory()->create([
        'post_content' => [
            'custom_fields' => [
                'hero.title'    => 'Hero Title Value',
                'hero.subtitle' => 'Hero Subtitle Value',
            ],
        ],
    ]);

    expect($page->custom_fields['hero.title'])->toBe('Hero Title Value')
        ->and($page->custom_fields['hero.subtitle'])->toBe('Hero Subtitle Value');
});

it('renders boolean fields correctly', function () {
    $renderer = app(TemplateFieldRenderer::class);

    expect($renderer->render('toggle', true))->toBeTrue()
        ->and($renderer->render('toggle', false))->toBeFalse()
        ->and($renderer->render('checkbox', 1))->toBeTrue()
        ->and($renderer->render('checkbox', 0))->toBeFalse();
});

it('escapes HTML in text fields for security', function () {
    $renderer = app(TemplateFieldRenderer::class);

    $maliciousContent = '<script>alert("XSS")</script>';
    $result = $renderer->render('text', $maliciousContent);

    expect($result)->not->toContain('<script>')
        ->and($result)->toContain('&lt;script&gt;');
});

it('does not escape HTML in richEditor fields', function () {
    $renderer = app(TemplateFieldRenderer::class);

    $htmlContent = '<p>Valid <strong>HTML</strong></p>';
    $result = $renderer->render('richEditor', $htmlContent);

    expect($result->toHtml())->toBe($htmlContent)
        ->and($result->toHtml())->toContain('<p>')
        ->and($result->toHtml())->toContain('<strong>');
});
