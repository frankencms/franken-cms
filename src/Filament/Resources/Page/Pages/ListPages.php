<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use FrankenCms\Filament\Resources\Page\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
