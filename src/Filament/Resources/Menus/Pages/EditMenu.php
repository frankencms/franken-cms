<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Menus\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Menus\MenuResource;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_items')
                ->label('Manage Menu Items')
                ->icon('heroicon-o-list-bullet')
                ->url(fn (): string => MenuResource::getUrl('manage-items', ['record' => $this->getRecord()])),
            DeleteAction::make(),
        ];
    }
}
