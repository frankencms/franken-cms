<?php

namespace FrankenCms\Filament\Resources\Menus\Schemas;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use FrankenCms\Filament\Resources\Menus\MenuResource;
use FrankenCms\Models\Menu;
use Illuminate\Support\Str;

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
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->modalHeading(fn (Menu $record): string => "Duplicate Menu: {$record->name}")
                    ->modalDescription('Create a copy of this menu with all its items. Enter a new name and slug for the duplicated menu.')
                    ->modalSubmitActionLabel('Duplicate Menu')
                    ->form(fn (Menu $record): array => [
                        TextInput::make('name')
                            ->label('Menu Name')
                            ->required()
                            ->maxLength(255)
                            ->default("{$record->name} (Copy)")
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->label('Menu Slug')
                            ->required()
                            ->maxLength(255)
                            ->default(Str::slug("{$record->slug}-copy"))
                            ->unique(Menu::class, 'slug')
                            ->helperText('The unique identifier for this menu.'),
                    ])
                    ->action(function (Menu $record, array $data): void {
                        $newMenu = $record->duplicateWithItems($data['name'], $data['slug']);

                        Notification::make()
                            ->title('Menu duplicated')
                            ->body("Menu \"{$newMenu->name}\" created with {$newMenu->allMenuItems()->count()} items.")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
