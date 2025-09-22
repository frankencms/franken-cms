<?php

namespace FrankenCms;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use FrankenCms\Filament\Resources\CmsSettings\Pages\CmsSettings;
use FrankenCms\Filament\Resources\Menus\MenuResource;
use FrankenCms\Filament\Resources\Page\PageResource;
use FrankenCms\Filament\Resources\Post\PostResource;
use FrankenCms\Filament\Resources\Taxonomy\TaxonomyResource;
use FrankenCms\Filament\Resources\Term\TermResource;
use FrankenCms\Filament\Resources\User\UserResource;

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
            ->resources([
                TaxonomyResource::class,
                TermResource::class,
                UserResource::class,
                PostResource::class,
                PageResource::class,
                MenuResource::class,
            ])
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
