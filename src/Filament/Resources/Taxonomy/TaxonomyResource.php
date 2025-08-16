<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Taxonomy;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Taxonomy\Schemas\TaxonomyForm;
use FrankenCms\Filament\Resources\Taxonomy\Schemas\TaxonomyTable;
use FrankenCms\Models\Taxonomy;

class TaxonomyResource extends Resource
{
    protected static ?string $model = Taxonomy::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::RectangleGroup;
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Schema $schema): Schema
    {
        return TaxonomyForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxonomyTable::make($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTaxonomies::route('/'),
            'create' => Pages\CreateTaxonomy::route('/create'),
            'edit'   => Pages\EditTaxonomy::route('/{record}/edit'),
        ];
    }
}
