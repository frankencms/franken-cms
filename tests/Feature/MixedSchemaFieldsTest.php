<?php

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use FrankenCms\Services\CmsFieldBuilder;

it('builds schema from array definitions', function () {
    $builder = app(CmsFieldBuilder::class);

    $schema = $builder->buildSchema([
        ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'required' => true],
        ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 3],
    ]);

    expect($schema)->toHaveCount(2)
        ->and($schema[0])->toBeInstanceOf(Field::class)
        ->and($schema[1])->toBeInstanceOf(Field::class);
});

it('builds schema from Filament Component instances', function () {
    $builder = app(CmsFieldBuilder::class);

    $schema = $builder->buildSchema([
        TextInput::make('title')->label('Title')->required(),
        Textarea::make('description')->label('Description')->rows(3),
    ]);

    expect($schema)->toHaveCount(2)
        ->and($schema[0])->toBeInstanceOf(TextInput::class)
        ->and($schema[1])->toBeInstanceOf(Textarea::class);
});

it('builds schema from mixed array and Component definitions', function () {
    $builder = app(CmsFieldBuilder::class);

    $schema = $builder->buildSchema([
        // Array definition
        ['name' => 'icon', 'type' => 'text', 'label' => 'Icon', 'default' => '🚀'],
        // Filament Component
        TextInput::make('title')->label('Title')->required(),
        // Another array definition
        ['name' => 'description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 3],
    ]);

    expect($schema)->toHaveCount(3)
        ->and($schema[0])->toBeInstanceOf(Field::class)
        ->and($schema[1])->toBeInstanceOf(TextInput::class)
        ->and($schema[2])->toBeInstanceOf(Field::class);
});

it('throws exception if array definition missing name', function () {
    $builder = app(CmsFieldBuilder::class);

    $builder->buildSchemaField([
        'type' => 'text',
        'label' => 'Missing Name',
    ]);
})->throws(\InvalidArgumentException::class, 'Field definition array must have a "name" key');

it('defaults to text type if not specified', function () {
    $builder = app(CmsFieldBuilder::class);

    $field = $builder->buildSchemaField([
        'name' => 'test_field',
        'label' => 'Test Field',
    ]);

    expect($field)->toBeInstanceOf(TextInput::class);
});

it('applies options from array definition', function () {
    $builder = app(CmsFieldBuilder::class);

    $field = $builder->buildSchemaField([
        'name' => 'description',
        'type' => 'textarea',
        'label' => 'Description',
        'required' => true,
        'rows' => 5,
    ]);

    expect($field)->toBeInstanceOf(Field::class);
});
