<?php

namespace FrankenCms;

use Composer\InstalledVersions;
use FrankenCms\Commands\InstallCommand;
use FrankenCms\Registries\SettingsTabRegistry;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\PostService;
use FrankenCms\Services\SettingsTabService;
use FrankenCms\View\Components\CmsField;
use FrankenCms\View\Components\CmsPost;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FrankenCmsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('franken-cms')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                '00_create_settings_table',
                '01_create_general_settings',
                '02_create_reading_settings',
                '03_create_media_settings',
                '04_create_permalink_settings',
                '05_create_posts_table',
                '06_create_usermeta_table',
                '07_create_taxonomies_table',
                '08_create_terms_table',
                '09_create_termables_table',
            ])
            ->hasTranslations()
//            ->hasRoutes('web')
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register the settings tab registry as a singleton
        $this->app->singleton(SettingsTabRegistry::class);

        // Register the settings tab service
        $this->app->singleton(SettingsTabService::class, function ($app) {
            return new SettingsTabService($app->make(SettingsTabRegistry::class));
        });
    }

    public function packageBooted(): void
    {
        $this->app->singleton(CurrentPageService::class);
        $this->app->singleton(PostService::class);

        Blade::component('cms-field', CmsField::class);
        Blade::component('cms-post', CmsPost::class);

        // Register the default tabs
        $settingsTabService = $this->app->make(SettingsTabService::class);
        $settingsTabService->registerDefaultTabs();

        $this->registerAboutInfo();
    }

    private function registerAboutInfo()
    {

        if ($this->app->runningInConsole()) {
            if (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class)) {

                AboutCommand::add('Franken CMS 🧟‍♂️', [
                    'Version' => InstalledVersions::getPrettyVersion('frankencms/franken-cms'),
                    //                    'Plugins' => collect()
                    //                        ->join(', '),
                    //                    'XXXX' => function (): string {
                    //                        $publishedViewPaths = collect(array_keys(config('master-forms.components')))
                    //                            ->filter(fn (string $form): bool => is_dir(resource_path("views/vendor/{$form}")));
                    //
                    //                        if (! $publishedViewPaths->count()) {
                    //                            return '<fg=green;options=bold>NOT PUBLISHED</>';
                    //                        }
                    //
                    //                        return "<fg=red;options=bold>PUBLISHED:</> {$publishedViewPaths->join(', ')}";
                    //                    },
                ]);

            }
        }

    }
}
