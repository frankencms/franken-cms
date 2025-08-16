<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Page\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
