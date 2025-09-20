<?php

namespace FrankenCms\Filament\Resources\CmsSettings\Schemas;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use FrankenCms\Services\SettingsTabService;

class CmsSettingsSchema
{
    public static function make(Schema $schema): Schema
    {
        $settingsTabService = app(SettingsTabService::class);

        return $schema->components([
            Tabs::make('Tabs')
                ->persistTabInQueryString('settings-tab')
                ->columnSpanFull()
                ->tabs($settingsTabService->getRegistry()->getTabs()),
        ]);
    }

}
