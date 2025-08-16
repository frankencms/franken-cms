<?php

namespace FrankenCms\Filament\Resources\Taxonomy\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use FrankenCms\Filament\Resources\Taxonomy\TaxonomyResource;

class ListTaxonomies extends ListRecords
{
    protected static string $resource = TaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
