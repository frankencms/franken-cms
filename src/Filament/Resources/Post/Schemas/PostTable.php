<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Post\Schemas;

use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                CuratorColumn::make('featured_image_id')
                    ->label('')
                    ->size(50),
                TextColumn::make('post_title')->sortable()->searchable(),
                TextColumn::make('post_slug')->sortable()->searchable(),
                TextColumn::make('terms.name')->label('Terms')->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
