<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Menus;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Menus\Schemas\MenusForm;
use FrankenCms\Filament\Resources\Menus\Schemas\MenusTable;
use FrankenCms\Models\Menu;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string | null | BackedEnum $navigationIcon = 'heroicon-o-bars-3';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Schema $schema): Schema
    {
        return MenusForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::make($table);
    }

    public static function getPages(): array
    {
        return [
            'index'        => Pages\ListMenus::route('/'),
            'create'       => Pages\CreateMenu::route('/create'),
            'edit'         => Pages\EditMenu::route('/{record}/edit'),
            'manage-items' => Pages\ManageMenuItems::route('/{record}/items'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }
}
