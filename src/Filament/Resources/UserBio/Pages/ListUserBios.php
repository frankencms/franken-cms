<?php

namespace FrankenCms\Filament\Resources\UserBio\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use FrankenCms\Filament\Resources\UserBio\UserBioResource;

class ListUserBios extends ListRecords
{
    protected static string $resource = UserBioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
