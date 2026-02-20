<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\UserBio;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\UserBio\Pages\CreateUserBio;
use FrankenCms\Filament\Resources\UserBio\Pages\EditUserBio;
use FrankenCms\Filament\Resources\UserBio\Pages\ListUserBios;
use FrankenCms\Filament\Resources\UserBio\Schemas\UserBioForm;
use FrankenCms\Filament\Resources\UserBio\Schemas\UserBioTable;
use FrankenCms\Models\UserBio;

class UserBioResource extends Resource
{
    protected static ?string $model = UserBio::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::UserCircle;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'User Bios';

    protected static ?string $modelLabel = 'User Bio';

    protected static ?string $pluralModelLabel = 'User Bios';

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Schema $schema): Schema
    {
        return UserBioForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return UserBioTable::make($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUserBios::route('/'),
            'create' => CreateUserBio::route('/create'),
            'edit'   => EditUserBio::route('/{record}/edit'),
        ];
    }
}
