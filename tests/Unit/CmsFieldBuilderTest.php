<?php

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use FrankenCms\Services\CmsFieldBuilder;

it('builds TextInput field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'title',
        'type' => 'text',
        'options' => ['label' => 'Title', 'required' => true],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(TextInput::class);
});

it('builds Textarea field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'description',
        'type' => 'textarea',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(Textarea::class);
});

it('builds Select field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'status',
        'type' => 'select',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(Select::class);
});

it('builds FileUpload field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'avatar',
        'type' => 'file',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(FileUpload::class);
});

it('builds image field with imageEditor', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'featured_image',
        'type' => 'image',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(FileUpload::class);
});

it('builds Repeater field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'items',
        'type' => 'repeater',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(Repeater::class);
});

it('builds RichEditor field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'content',
        'type' => 'richEditor',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(RichEditor::class);
});

it('builds Toggle field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'is_active',
        'type' => 'toggle',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(Toggle::class);
});

it('builds Checkbox field from definition', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'agree',
        'type' => 'checkbox',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(Checkbox::class);
});

it('applies email validation to email fields', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'email',
        'type' => 'email',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(TextInput::class);
});

it('applies url validation to url fields', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'website',
        'type' => 'url',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(TextInput::class);
});

it('applies numeric validation to number fields', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'age',
        'type' => 'number',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(TextInput::class);
});

it('uses custom_fields prefix for field names', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'hero.title',
        'type' => 'text',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field->getName())->toBe('custom_fields.hero.title');
});

it('builds multiple fields from array of definitions', function () {
    $builder = new CmsFieldBuilder;

    $definitions = [
        ['name' => 'title', 'type' => 'text', 'options' => []],
        ['name' => 'content', 'type' => 'textarea', 'options' => []],
        ['name' => 'is_active', 'type' => 'toggle', 'options' => []],
    ];

    $fields = $builder->buildFields($definitions);

    expect($fields)->toHaveCount(3)
        ->and($fields[0])->toBeInstanceOf(TextInput::class)
        ->and($fields[1])->toBeInstanceOf(Textarea::class)
        ->and($fields[2])->toBeInstanceOf(Toggle::class);
});

it('can register custom field type', function () {
    $builder = new CmsFieldBuilder;

    $builder->registerFieldType('custom', TextInput::class);

    $fieldTypes = $builder->getFieldTypeMap();

    expect($fieldTypes)->toHaveKey('custom')
        ->and($fieldTypes['custom'])->toBe(TextInput::class);
});

it('falls back to TextInput for unknown field types', function () {
    $builder = new CmsFieldBuilder;

    $definition = [
        'name' => 'unknown',
        'type' => 'unknown_type',
        'options' => [],
    ];

    $field = $builder->buildField($definition);

    expect($field)->toBeInstanceOf(TextInput::class);
});
