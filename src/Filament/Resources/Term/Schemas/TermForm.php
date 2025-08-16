<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Term\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use Illuminate\Support\Str;

class TermForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->live()
                    ->required(),
                TextInput::make('slug')
                    ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state)))
                    ->unique(ignoreRecord: true),
                Select::make('taxonomy_id')
                    ->label('Taxonomy')
                    ->options(Taxonomy::all()->pluck('name', 'id'))
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent Term')
                    ->options(fn ($get) => Term::where('taxonomy_id', $get('taxonomy_id'))->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }
}
