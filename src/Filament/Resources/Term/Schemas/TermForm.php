<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Term\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                Section::make('Term Details')
                    ->description('Configure the term name, slug, and taxonomy')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $context, $state, Set $set) {
                                if ($context === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255)
                            ->unique(Term::class, 'slug', ignoreRecord: true)
                            ->helperText('URL-friendly version of the name'),

                        Select::make('taxonomy_id')
                            ->label('Taxonomy')
                            ->inlineLabel()
                            ->options(Taxonomy::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live(),

                        Select::make('parent_id')
                            ->label('Parent Term')
                            ->inlineLabel()
                            ->options(fn (Get $get) => Term::where('taxonomy_id', $get('taxonomy_id'))->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Optional: Set a parent term to create a hierarchy')
                            ->visible(fn (Get $get) => filled($get('taxonomy_id'))),

                        Textarea::make('description')
                            ->label('Description')
                            ->inlineLabel()
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Optional description for this term')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
