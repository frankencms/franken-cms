<?php

namespace FrankenCms;

use Composer\InstalledVersions;
use FrankenCms\Commands\InstallCommand;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\PostService;
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
                '01_create_cms_settings',
                '02_create_posts_table',
                '03_create_usermeta_table',
                '04_create_taxonomies_table',
                '05_create_terms_table',
                '06_create_termables_table',
            ])
            ->hasTranslations()
//            ->hasRoutes('web')
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->singleton(CurrentPageService::class);
        $this->app->singleton(PostService::class);

        Blade::component('cms-field', CmsField::class);
        Blade::component('cms-post', CmsPost::class);

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
