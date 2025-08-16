<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Page;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Page\Schemas\PageForm;
use FrankenCms\Filament\Resources\Page\Schemas\PageTable;
use FrankenCms\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::DocumentText;
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return PageTable::make($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
