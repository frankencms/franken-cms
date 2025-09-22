<?php

namespace FrankenCms\Filament\Resources\Menus\Schemas;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Menus\MenuResource;
use FrankenCms\Models\Menu;

class MenusTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('all_menu_items_count')
                    ->label('Items')
                    ->counts('allMenuItems')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                //
            ])
            ->recordActions([
                //                ViewAction::make(),
                EditAction::make(),
                Action::make('manage_items')
                    ->label('Manage Items')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (Menu $record): string => MenuResource::getUrl('manage-items', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
