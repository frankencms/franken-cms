<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Term\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TermTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('taxonomy.name')->label('Taxonomy')->sortable(),
                TextColumn::make('parent.name')->label('Parent Term')->sortable(),
            ])
            ->filters([]);
    }
}
