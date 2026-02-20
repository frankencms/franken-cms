<?php

namespace FrankenCms\Tests\Support;

use Filament\Panel;
use Filament\PanelProvider;
use FrankenCms\FrankenCmsPlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(
                new FrankenCmsPlugin
            );
    }
}
