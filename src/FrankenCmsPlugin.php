<?php

namespace FrankenCms;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use FrankenCms\Filament\Resources\CmsSettings\Pages\CmsSettings;

class FrankenCmsPlugin implements Plugin
{
    use EvaluatesClosures;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'frankencms';
    }

    public function register(Panel $panel): void
    {

        $panel
            ->resources([])
            ->pages([
                CmsSettings::class,
            ])
            ->navigationGroups([])
            ->plugins([]);

    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
