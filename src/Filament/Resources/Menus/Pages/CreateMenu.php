<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Menus\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Menus\MenuResource;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('manage-items', ['record' => $this->getRecord()]);
    }
}
