<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Taxonomy\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaxonomyForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Taxonomy Details')
                    ->description('Define the taxonomy name and structure type')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),

                        Toggle::make('hierarchical')
                            ->label('Hierarchical')
                            ->inlineLabel()
                            ->helperText('Enable parent-child relationships for this taxonomy')
                            ->default(false),
                    ]),
            ]);
    }
}
