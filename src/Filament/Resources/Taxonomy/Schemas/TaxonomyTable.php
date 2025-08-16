<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Taxonomy\Schemas;

use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxonomyTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                BooleanColumn::make('hierarchical')->label('Hierarchical'),
            ])
            ->filters([]);
    }
}
