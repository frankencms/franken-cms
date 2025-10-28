<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Page\Schemas;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post_title')->sortable()->searchable(),
                TextColumn::make('post_slug')->sortable()->searchable(),
                TextColumn::make('route_name')->sortable()->searchable(),
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
