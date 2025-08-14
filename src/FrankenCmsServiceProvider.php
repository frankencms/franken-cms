<?php

namespace FrankenCms;

use Composer\InstalledVersions;
use FrankenCms\Commands\FrankenCmsCommand;
use Illuminate\Foundation\Console\AboutCommand;
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
            ->hasMigration('o1_create_cms_settings')
            ->hasCommand(FrankenCmsCommand::class);
    }

    private function registerAboutInfo()
    {

        if ($this->app->runningInConsole()) {
            if (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class)) {

                AboutCommand::add('Franken CMS', [
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
