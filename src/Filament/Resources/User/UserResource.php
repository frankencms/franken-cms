<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\User;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\User\Schemas\UserForm;
use FrankenCms\Filament\Resources\User\Schemas\UserTable;

class UserResource extends Resource
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::Users;

    protected static ?int $navigationSort = 5;

    public static function getModel(): string
    {
        return config('franken-cms.models.user');
    }

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');

    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTable::make($table);
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
