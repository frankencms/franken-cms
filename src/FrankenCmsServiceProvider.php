<?php

namespace FrankenCms;

use Composer\InstalledVersions;
use Exception;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use FrankenCms\Commands\GenerateSitemapCommand;
use FrankenCms\Commands\InstallCommand;
use FrankenCms\Registries\SettingsTabRegistry;
use FrankenCms\Services\BladeFormDirectiveProcessor;
use FrankenCms\Services\BladeFormDirectiveRegistry;
use FrankenCms\Services\CmsFieldBuilder;
use FrankenCms\Services\CmsFieldRenderer;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\MenuService;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Services\PostService;
use FrankenCms\Services\SettingsTabService;
use FrankenCms\Services\TemplateFieldParser;
use FrankenCms\View\Components\CmsField;
use FrankenCms\View\Components\CmsPost;
use FrankenCms\View\Composers\CmsFieldComposer;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
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
                '05_create_seo_settings',
                '06_create_posts_table',
                '07_create_usermeta_table',
                '08_create_taxonomies_table',
                '09_create_terms_table',
                '10_create_termables_table',
                '11_seed_default_taxonomies',
                '12_create_media_table',
                '13_create_menus_table',
                '14_create_postmeta_table',
                '15_add_hierarchy_and_routes_to_posts',
                '16_remove_homepage_displays_setting',
                '17_create_seo_media_table',
                '18_create_robots_settings',
                '19_create_sitemap_settings',
            ])
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasCommands([
                InstallCommand::class,
                GenerateSitemapCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Load helpers file
        require_once __DIR__ . '/helpers.php';

        // Register the settings tab registry as a singleton
        $this->app->singleton(SettingsTabRegistry::class);

        // Register the settings tab service
        $this->app->singleton(SettingsTabService::class, function ($app) {
            return new SettingsTabService($app->make(SettingsTabRegistry::class));
        });

        // Register the menu service
        $this->app->singleton(MenuService::class);

        // Register the blade form directive registry
        $this->app->singleton(BladeFormDirectiveRegistry::class);

        // Register the blade form directive processor
        $this->app->singleton(BladeFormDirectiveProcessor::class);

        // Register the page route service
        $this->app->singleton(PageRouteService::class);

        // Register custom field services
        $this->app->singleton(CmsFieldRenderer::class);
        $this->app->singleton(TemplateFieldParser::class);
        $this->app->singleton(CmsFieldBuilder::class);

        // Register robots and sitemap services
        $this->app->singleton(\FrankenCms\Services\RobotsService::class);
        $this->app->singleton(\FrankenCms\Services\SitemapService::class);

        // Register the SEO service provider
        $this->app->register(\FrankenCms\Providers\SeoServiceProvider::class);
    }

    public function packageBooted(): void
    {
        $this->app->singleton(CurrentPageService::class);
        $this->app->singleton(PostService::class);

        // Register middlewares to web group
        $router = $this->app['router'];
        // SetCurrentPage must run before AddSeoDefaults so the SEO service can access the current page
        $router->pushMiddlewareToGroup('web', \FrankenCms\Http\Middleware\SetCurrentPage::class);
        $router->pushMiddlewareToGroup('web', \FrankenCms\Http\Middleware\AddSeoDefaults::class);

        Blade::component('cms-field', CmsField::class);
        Blade::component('cms-post', CmsPost::class);

        // Register view composer to pre-populate CMS fields
        $this->registerCmsFieldComposer();

        // Register theme components directory
        // This allows themes to have their own self-contained components
        $this->registerThemeComponents();

        // Register custom blade directives
        $this->registerBladeDirectives();

        // Initialize the blade form directive registry
        $this->app->make(BladeFormDirectiveRegistry::class);

        // Register the default tabs
        $settingsTabService = $this->app->make(SettingsTabService::class);
        $settingsTabService->registerDefaultTabs();

        $this->registerAboutInfo();

        FilamentAsset::register([
            Js::make(
                id: 'enhanced-image',
                path: __DIR__ . '/../resources/dist/filament/rich-content-plugins/enhanced-image.js'
            )
                ->loadedOnRequest(),

            AlpineComponent::make(
                id: 'focal-point-picker',
                path: __DIR__ . '/../resources/dist/focal-point-picker.js'
            ),

            AlpineComponent::make(
                id: 'featured-image-focal-picker',
                path: __DIR__ . '/../resources/dist/featured-image-focal-picker.js'
            ),

        ], 'frankencms/franken-cms');

    }


    private function registerThemeComponents(): void
    {
        $themeFolder = config('franken-cms.theme_folder', 'theme');
        $componentsPath = resource_path("views/{$themeFolder}/components");

        // Only register if the components directory exists
        if (is_dir($componentsPath)) {
            Blade::anonymousComponentPath(
                $componentsPath,
                'theme'
            );
        }
    }

    private function registerBladeDirectives(): void
    {
        // Register @menu directive
        Blade::directive('menu', function ($expression) {
            return "<?php
                \$__menuSlug = {$expression};
                \$__menuService = app(\FrankenCms\Services\MenuService::class);
                \$menuItems = \$__menuService->getMenuItems(\$__menuSlug);
            ?>";
        });

        // Register @endmenu directive
        Blade::directive('endmenu', function () {
            return '<?php unset($menuItems, $__menuSlug, $__menuService); ?>';
        });

        // Register @cmsField directive
        // Adds all fields to $cmsFields collection and echoes non-repeater fields
        Blade::directive('cmsField', function ($expression) {
            // Extract field name from expression
            if (preg_match("/^['\"]([^'\"]+)['\"]/", $expression, $matches)) {
                $fieldName = $matches[1];
                $varName = cmsFieldVariableName($fieldName);

                // Check if it's a repeater
                $isRepeater = preg_match("/^[^,]+,\s*['\"]repeater['\"]/", $expression);

                if ($isRepeater) {
                    // Repeater: add to collection but don't echo
                    return "<?php
                        if (!isset(\$cmsFields)) { \$cmsFields = collect(); view()->share('cmsFields', \$cmsFields); }
                        // Only render if not already populated by view composer
                        if (!\$cmsFields->has('{$varName}')) {
                            \$cmsFields['{$varName}'] = _renderCmsField({$expression});
                            view()->share('cmsFields', \$cmsFields);
                        }
                    ?>";
                } else {
                    // Non-repeater: add to collection AND echo
                    return "<?php
                        if (!isset(\$cmsFields)) { \$cmsFields = collect(); view()->share('cmsFields', \$cmsFields); }
                        // Only render if not already populated by view composer
                        if (!\$cmsFields->has('{$varName}')) {
                            \$_fieldValue = _renderCmsField({$expression});
                            \$cmsFields['{$varName}'] = \$_fieldValue;
                            view()->share('cmsFields', \$cmsFields);
                        } else {
                            \$_fieldValue = \$cmsFields->get('{$varName}');
                        }
                        echo \$_fieldValue;
                    ?>";
                }
            }

            // Fallback - just echo
            return "<?php echo _renderCmsField({$expression}); ?>";
        });
    }

    private function registerCmsFieldComposer(): void
    {
        // Register the composer for all views
        // The composer itself will check if it's a theme template
        View::composer('*', CmsFieldComposer::class);
    }

    private function registerAboutInfo(): void
    {

        if ($this->app->runningInConsole()) {
            if (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class)) {

                AboutCommand::add('🧟 FRANKEN CMS - Alive & Wel', fn () => [

                    'Version'          => InstalledVersions::getPrettyVersion('frankencms/franken-cms') ?? 'Unknown',
                    'Theme'            => config('franken-cms.theme_folder', 'theme'),
                    'Theme Components' => function (): string {
                        $themeFolder = config('franken-cms.theme_folder', 'theme');
                        $componentsPath = resource_path("views/{$themeFolder}/components");

                        if (is_dir($componentsPath)) {
                            $components = glob($componentsPath . '/*.blade.php');
                            $count = count($components);

                            if ($count > 0) {
                                $names = collect($components)
                                    ->map(fn ($path) => basename($path, '.blade.php'))
                                    ->take(5)
                                    ->join(', ');
                                $extra = $count > 5 ? ' (+' . ($count - 5) . ' more)' : '';
                                return "<fg=green;options=bold>{$count} registered:</> {$names}{$extra}";
                            }
                            return '<fg=yellow>No components found</>';
                        }

                        return '<fg=gray>Not configured</>';
                    },
                    'Settings Tabs' => function (): string {
                        try {
                            $registry = app(SettingsTabRegistry::class);
                            $tabs = $registry->getTabs();
                            $count = count($tabs);

                            if ($count > 0) {
                                $tabNames = collect($tabs)->keys()->take(3)->join(', ');
                                $extra = $count > 3 ? ' (+' . ($count - 3) . ' more)' : '';
                                return "<fg=green;options=bold>{$count} tab(s):</> {$tabNames}{$extra}";
                            }
                            return '<fg=yellow>None registered</>';
                        } catch (Exception $e) {
                            return '<fg=gray>N/A</>';
                        }
                    },
                    'Content Stats' => function (): string {
                        try {
                            $published = \FrankenCms\Models\Post::where('status', 'published')->count();
                            $draft = \FrankenCms\Models\Post::where('status', 'draft')->count();
                            $pages = \FrankenCms\Models\Post::where('type', 'page')->count();

                            $parts = [];
                            if ($published > 0) {
                                $parts[] = "<fg=green>{$published} published</>";
                            }
                            if ($draft > 0) {
                                $parts[] = "<fg=yellow>{$draft} draft</>";
                            }
                            if ($pages > 0) {
                                $parts[] = "<fg=cyan>{$pages} pages</>";
                            }

                            return $parts ? implode(' • ', $parts) : '<fg=gray>No content</>';
                        } catch (Exception $e) {
                            return '<fg=gray>N/A</>';
                        }
                    },
                    'Taxonomies' => function (): string {
                        try {
                            $taxonomies = \FrankenCms\Models\Taxonomy::count();
                            $terms = \FrankenCms\Models\Term::count();

                            if ($taxonomies > 0 || $terms > 0) {
                                return "<fg=green;options=bold>{$taxonomies} taxonomies</> with <fg=green;options=bold>{$terms} terms</>";
                            }
                            return '<fg=yellow>None configured</>';
                        } catch (Exception $e) {
                            return '<fg=gray>N/A</>';
                        }
                    },

                    'Menus' => function (): string {
                        try {
                            $menus = \FrankenCms\Models\Menu::all();
                            $count = $menus->count();

                            if ($count > 0) {
                                $names = $menus->pluck('slug')->take(3)->join(', ');
                                $extra = $count > 3 ? ' (+' . ($count - 3) . ' more)' : '';
                                return "<fg=green;options=bold>{$count} menu(s):</> {$names}{$extra}";
                            }
                            return '<fg=yellow>No menus</>';
                        } catch (Exception $e) {
                            return '<fg=gray>N/A</>';
                        }
                    },
                    'Custom Blade Directives' => '<fg=green;options=bold>@menu</>, <fg=green;options=bold>@endmenu</>, <fg=green;options=bold>@cmsField</>',
                    'Filament Components'     => function (): string {
                        $components = [
                            'Enhanced Image Editor',
                            'Focal Point Picker',
                            'Featured Image Picker',
                        ];
                        return '<fg=cyan>' . implode('</>, <fg=cyan>', $components) . '</>';
                    },
                    'Blade Components' => '<fg=cyan><x-cms-field /></>, <fg=cyan><x-cms-post /></>',

                    'Routes Cached' => fn () => app()->routesAreCached()
                        ? '<fg=green;options=bold>YES</>'
                        : '<fg=yellow;options=bold>NO</>',
                    'Config Cached' => fn () => app()->configurationIsCached()
                        ? '<fg=green;options=bold>YES</>'
                        : '<fg=yellow;options=bold>NO</>',
                    'Theme Path' => function (): string {
                        $themeFolder = config('franken-cms.theme_folder', 'theme');
                        $themePath = resource_path("views/{$themeFolder}");

                        if (is_dir($themePath)) {
                            return "<fg=green;options=bold>✓</> {$themePath}";
                        }
                        return "<fg=red;options=bold>✗</> {$themePath} <fg=red>(missing)</>";
                    },
                    'Installed Plugins' => function (): string {
                        // If you have a plugin system, list them here
                        // For now, return a placeholder
                        return '<fg=gray>Coming soon...</>';
                    },
                ]);

                //                AboutCommand::add('Franken CMS 🧟', [
                //                    'Version' => InstalledVersions::getPrettyVersion('frankencms/franken-cms'),
                //                    //                    'Plugins' => collect()
                //                    //                        ->join(', '),
                //                    //                    'XXXX' => function (): string {
                //                    //                        $publishedViewPaths = collect(array_keys(config('master-forms.components')))
                //                    //                            ->filter(fn (string $form): bool => is_dir(resource_path("views/vendor/{$form}")));
                //                    //
                //                    //                        if (! $publishedViewPaths->count()) {
                //                    //                            return '<fg=green;options=bold>NOT PUBLISHED</>';
                //                    //                        }
                //                    //
                //                    //                        return "<fg=red;options=bold>PUBLISHED:</> {$publishedViewPaths->join(', ')}";
                //                    //                    },
                //                ]);

            }
        }

    }
}
