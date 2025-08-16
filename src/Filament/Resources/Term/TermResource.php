<?php

namespace FrankenCms\Filament\Resources\Term;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use Illuminate\Support\Str;

class TermResource extends Resource
{
    protected static ?string $model = Term::class;

    //    protected static ?string $navigationGroup = 'Taxonomy';
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->live()
                    ->required(),
                TextInput::make('slug')
                    // auto create slug from name
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

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('taxonomy.name')->label('Taxonomy')->sortable(),
                TextColumn::make('parent.name')->label('Parent Term')->sortable(),
            ])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTerms::route('/'),
            'create' => Pages\CreateTerm::route('/create'),
            'edit'   => Pages\EditTerm::route('/{record}/edit'),
        ];
    }
}
