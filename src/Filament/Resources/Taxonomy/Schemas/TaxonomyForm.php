<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Taxonomy\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxonomyForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                Toggle::make('hierarchical')->label('Hierarchical?'),
            ]);
    }
}
