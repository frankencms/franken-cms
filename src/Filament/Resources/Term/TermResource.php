<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Term;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Term\Schemas\TermForm;
use FrankenCms\Filament\Resources\Term\Schemas\TermTable;
use FrankenCms\Models\Term;

class TermResource extends Resource
{
    protected static ?string $model = Term::class;

    protected static ?int $navigationSort = 4;
    protected static string | BackedEnum | null $navigationIcon = Heroicon::Tag;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Schema $schema): Schema
    {
        return TermForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return TermTable::make($table);
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
