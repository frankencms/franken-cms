<?php

namespace FrankenCms\Filament\Resources\User\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use FrankenCms\Filament\Resources\User\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
