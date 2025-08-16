<?php

namespace FrankenCms\Filament\Resources\Post\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use FrankenCms\Filament\Resources\Post\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
