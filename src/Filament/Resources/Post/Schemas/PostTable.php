<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Post\Schemas;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured')
                    ->label('')
                    ->collection('featured')
                    ->conversion('thumb')
                    ->imageSize(60)
                    ->square()
                    ->extraImgAttributes(['style' => 'border-radius: 0.5rem;'])
                    ->defaultImageUrl(fn () => null),
                TextColumn::make('post_title')->sortable()->searchable(),
                TextColumn::make('post_slug')->sortable()->searchable(),
                TextColumn::make('terms.name')->label('Terms')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
