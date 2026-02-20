<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Post;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Post\Schemas\PostForm;
use FrankenCms\Filament\Resources\Post\Schemas\PostTable;
use FrankenCms\Models\Post;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::RectangleStack;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return PostTable::make($table);
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
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
