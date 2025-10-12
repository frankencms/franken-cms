<?php

namespace FrankenCms\Filament\Resources\Page\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\Page\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    // The HasMeta trait automatically handles meta fields like 'template'
    // via the setAttribute() override and bootHasMeta() event
    // No need for manual hooks here
}
