<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Term\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use Illuminate\Support\Str;

class TermForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->inlineLabel()
                    ->live()
                    ->required(),
                TextInput::make('slug')
                    ->inlineLabel()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state)))
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Description')
                    ->inlineLabel()
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
                Select::make('taxonomy_id')
                    ->label('Taxonomy')
                    ->inlineLabel()
                    ->options(Taxonomy::all()->pluck('name', 'id'))
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent Term')
                    ->inlineLabel()
                    ->options(fn (Get $get) => Term::where('taxonomy_id', $get('taxonomy_id'))->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }
}
