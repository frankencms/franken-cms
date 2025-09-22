<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Menus\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use FrankenCms\Filament\Resources\Menus\MenuResource;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
