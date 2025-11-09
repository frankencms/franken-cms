<?php

namespace FrankenCms;

use BladeUI\Icons\Factory;
use Composer\InstalledVersions;
use Exception;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use FrankenCms\Commands\GenerateSitemapCommand;
use FrankenCms\Commands\InstallCommand;
use FrankenCms\Http\Middleware\AddSeoDefaults;
use FrankenCms\Http\Middleware\SetCurrentPage;
use FrankenCms\Listeners\ClearFeedCacheListener;
use FrankenCms\Listeners\ClearRobotsCacheListener;
use FrankenCms\Listeners\ClearSitemapCacheListener;
use FrankenCms\Listeners\RegeneratePostImagesListener;
use FrankenCms\Livewire\BlogPostWizard;
use FrankenCms\Models\Menu;
use FrankenCms\Models\Post;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use FrankenCms\Observers\PostObserver;
use FrankenCms\Prompts\PromptManager;
use FrankenCms\Providers\SeoServiceProvider;
use FrankenCms\Registries\SettingsTabRegistry;
use FrankenCms\Services\AiService;
use FrankenCms\Services\BladeFormDirectiveProcessor;
use FrankenCms\Services\BladeFormDirectiveRegistry;
use FrankenCms\Services\CmsFieldBuilder;
use FrankenCms\Services\CmsFieldRenderer;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\FeedService;
use FrankenCms\Services\MenuService;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Services\PostService;
use FrankenCms\Services\RobotsService;
use FrankenCms\Services\SettingsTabService;
use FrankenCms\Services\SitemapService;
use FrankenCms\Services\TemplateFieldParser;
use FrankenCms\Settings\StackSettings;
use FrankenCms\View\Components\CmsField;
use FrankenCms\View\Components\CmsPost;
use FrankenCms\View\Composers\CmsFieldComposer;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Prism\Prism\Prism;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelSettings\Events\SettingsSaved;

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
                '15_create_site_settings_media_table',
                '16_create_robots_settings',
                '17_create_sitemap_settings',
                '18_create_stack_settings',
                '19_create_ai_settings',
                '20_create_user_bios_table',
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

        // Register robots, sitemap, and feed services
        $this->app->singleton(RobotsService::class);
        $this->app->singleton(SitemapService::class);
        $this->app->singleton(FeedService::class);

        // Register the SEO service provider
        $this->app->register(SeoServiceProvider::class);

        // Register AI services (only if Prism is installed)
        if (class_exists(Prism::class)) {
            $this->app->singleton(PromptManager::class);
            $this->app->singleton(AiService::class);
        }

        // Register SVG Icons
        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('frankencms', [
                'path'   => __DIR__ . '/../resources/svg',
                'prefix' => 'frankencms', // <x-frankencms-camera/>
            ]);
        });

    }

    public function packageBooted(): void
    {
        $this->app->singleton(CurrentPageService::class);
        $this->app->singleton(PostService::class);

        // Register middlewares to web group
        $router = $this->app['router'];
        // SetCurrentPage must run before AddSeoDefaults so the SEO service can access the current page
        $router->pushMiddlewareToGroup('web', SetCurrentPage::class);
        $router->pushMiddlewareToGroup('web', AddSeoDefaults::class);

        // Register model observers
        Post::observe(PostObserver::class);

        // Register event listeners
        Event::listen(SettingsSaved::class, ClearSitemapCacheListener::class);
        Event::listen(SettingsSaved::class, ClearFeedCacheListener::class);
        Event::listen(SettingsSaved::class, ClearRobotsCacheListener::class);
        Event::listen(SettingsSaved::class, RegeneratePostImagesListener::class);

        Blade::component('cms-field', CmsField::class);
        Blade::component('cms-post', CmsPost::class);
        Blade::component('breadcrumbs', \FrankenCms\View\Components\Breadcrumbs::class);

        // Register breadcrumbs
        $this->registerBreadcrumbs();

        // Register view composer to pre-populate CMS fields
        $this->registerCmsFieldComposer();

        // Register view composer to inject custom code stacks
        $this->registerStackInjection();

        // Register theme components directory
        // This allows themes to have their own self-contained components
        $this->registerThemeComponents();

        // Register custom blade directives
        $this->registerBladeDirectives();
        $this->registerTypedFieldDirectives();

        // Initialize the blade form directive registry
        $this->app->make(BladeFormDirectiveRegistry::class);

        // Register the default tabs
        $settingsTabService = $this->app->make(SettingsTabService::class);
        $settingsTabService->registerDefaultTabs();

        // Register AI modal in Filament render hooks
        $this->registerAiModal();

        // Register Livewire components after Livewire is booted
        $this->app->booted(function () {
            $this->registerLivewireComponents();
        });

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

    private function registerLivewireComponents(): void
    {
        // Register the BlogPostWizard Livewire component (only if Prism is installed)
        if (class_exists(Prism::class)) {
            Livewire::component(
                'blog-post-wizard',
                BlogPostWizard::class
            );
        }
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
        // Helper to extract balanced brackets
        $extractBalancedArray = function ($str, $start) {
            $depth = 0;
            $inString = false;
            $stringChar = null;
            for ($i = $start; $i < strlen($str); $i++) {
                $char = $str[$i];
                if (! $inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar && ($i === 0 || $str[$i - 1] !== '\\')) {
                    $inString = false;
                } elseif (! $inString) {
                    if ($char === '[') {
                        $depth++;
                    }
                    if ($char === ']') {
                        $depth--;
                        if ($depth === 0) {
                            return substr($str, $start, $i - $start + 1);
                        }
                    }
                }
            }
            return null;
        };

        // Register @frankenMenu directive - works with automatic loop
        // Usage: @frankenMenu('menu-location') ... {{ $menuItem }} ... @endFrankenMenu
        Blade::directive('frankenMenu', function ($expression) use ($extractBalancedArray) {
            // Parse menu slug/location
            if (! preg_match('/^([\'"])(.*?)\1/', $expression, $nameMatch)) {
                return '<?php /* Invalid frankenMenu syntax */ ?>';
            }
            $menuSlug = $nameMatch[2];
            $after = substr($expression, strlen($nameMatch[0]));

            // Check for options array
            if (preg_match('/^\s*,\s*\[/', $after)) {
                $arrayStart = strpos($after, '[');
                $options = $extractBalancedArray($after, $arrayStart);
                $after = $options ? substr($after, $arrayStart + strlen($options)) : '';
            } else {
                $options = '[]';
            }

            // Check for placeholder boolean
            $placeholder = (preg_match('/^\s*,\s*(true|false)/', $after, $placeholderMatch)) ? $placeholderMatch[1] : 'true';

            return "<?php
                \$__menuSlug = '{$menuSlug}';
                \$__menuOpts = {$options};
                \$__showPlaceholder = {$placeholder};
                \$__menuService = app(\FrankenCms\Services\MenuService::class);
                \$__menuItems = \$__menuService->getMenuItems(\$__menuSlug);
                \$__currentUrl = request()->url();
                \$__menuService->addActiveState(\$__menuItems, \$__currentUrl);

                \$__menuIsEmpty = empty(\$__menuItems);
                ob_start();
                foreach (\$__menuItems as \$__menuItemData):
                    \$menuItem = \$__menuItemData;
            ?>";
        });

        Blade::directive('endFrankenMenu', function () {
            return <<<'PHP'
<?php
                endforeach;

                // Capture buffer content
                $__menuContent = ob_get_clean();

                // Check if has actual content
                $__menuHasContent = strlen(trim($__menuContent)) > 0;

                // Output content or placeholder
                if ($__menuHasContent) {
                    echo $__menuContent;
                } elseif ($__menuIsEmpty && $__showPlaceholder) {
                    // Show placeholder for empty menu
                    $__menuLabel = str_replace(['-', '_'], ' ', $__menuSlug);
                    $__menuLabel = ucwords($__menuLabel);
                    echo '<div style="display: inline-block; padding: 0.5rem 1rem; background: rgba(59, 130, 246, 0.05); border: 2px dashed rgba(59, 130, 246, 0.2); border-radius: 0.375rem; color: rgba(59, 130, 246, 0.6); font-size: 0.875rem;">';
                    echo '<svg style="display: inline-block; width: 1rem; height: 1rem; margin-right: 0.5rem; vertical-align: text-bottom;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>';
                    echo htmlspecialchars($__menuLabel) . ' (empty)';
                    echo '</div>';
                }

                unset($menuItem, $__menuItemData, $__menuItems, $__menuSlug, $__menuOpts, $__menuService, $__currentUrl, $__showPlaceholder, $__menuIsEmpty, $__menuContent, $__menuHasContent, $__menuLabel);
            ?>
PHP;
        });
    }

    private function registerTypedFieldDirectives(): void
    {
        $fieldTypes = [
            'Text'       => 'text',
            'Textarea'   => 'textarea',
            'Email'      => 'email',
            'Url'        => 'url',
            'Number'     => 'number',
            'Select'     => 'select',
            'File'       => 'file',
            'Image'      => 'image',
            'MediaImage' => 'image',
            'RichEditor' => 'richEditor',
            'Toggle'     => 'toggle',
            'Checkbox'   => 'checkbox',
            // Note: Tags is registered separately as a block directive below
        ];

        foreach ($fieldTypes as $directiveSuffix => $fieldType) {
            Blade::directive("franken{$directiveSuffix}", function ($expression) use ($fieldType) {
                return "<?php
                \$__fParams = _parseFieldExpression({$expression});
                \$__fName = \$__fParams['name'];
                \$__fOpts = \$__fParams['options'];
                \$__fVar = cmsFieldVariableName(\$__fName);

                if (!isset(\$frankenFields)) {
                    \$frankenFields = collect();
                    view()->share('frankenFields', \$frankenFields);
                }

                if (!\$frankenFields->has(\$__fVar)) {
                    \$__fValue = _renderCmsField(\$__fName, '{$fieldType}', \$__fOpts);
                    \$frankenFields[\$__fVar] = \$__fValue;
                    view()->share('frankenFields', \$frankenFields);
                } else {
                    \$__fValue = \$frankenFields->get(\$__fVar);
                }

                echo \$__fValue;
                unset(\$__fParams, \$__fName, \$__fOpts, \$__fVar, \$__fValue);
            ?>";
            });
        }

        // Helper to extract balanced brackets
        $extractBalancedArray = function ($str, $start) {
            $depth = 0;
            $inString = false;
            $stringChar = null;
            for ($i = $start; $i < strlen($str); $i++) {
                $char = $str[$i];
                if (! $inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar && ($i === 0 || $str[$i - 1] !== '\\')) {
                    $inString = false;
                } elseif (! $inString) {
                    if ($char === '[') {
                        $depth++;
                    }
                    if ($char === ']') {
                        $depth--;
                        if ($depth === 0) {
                            return substr($str, $start, $i - $start + 1);
                        }
                    }
                }
            }
            return null;
        };

        // Register repeater block directive
        // Usage: @frankenRepeater('field.items', [...options], false)
        Blade::directive('frankenRepeater', function ($expression) use ($extractBalancedArray) {
            // Parse field name
            if (! preg_match('/^([\'"])(.*?)\1/', $expression, $nameMatch)) {
                return '<?php /* Invalid frankenRepeater syntax */ ?>';
            }
            $fieldName = $nameMatch[2];
            $after = substr($expression, strlen($nameMatch[0]));

            // Check for options array
            if (preg_match('/^\s*,\s*\[/', $after)) {
                $arrayStart = strpos($after, '[');
                $options = $extractBalancedArray($after, $arrayStart);
                $after = $options ? substr($after, $arrayStart + strlen($options)) : '';
            } else {
                $options = '[]';
            }

            // Check for placeholder boolean
            $placeholder = (preg_match('/^\s*,\s*(true|false)/', $after, $placeholderMatch)) ? $placeholderMatch[1] : 'true';

            return "<?php
                \$__rptName = '{$fieldName}';
                \$__rptOpts = {$options};
                \$__rptShowPlaceholder = {$placeholder};
                \$__rptVar = cmsFieldVariableName(\$__rptName);

                if (!isset(\$frankenFields)) {
                    \$frankenFields = collect();
                    view()->share('frankenFields', \$frankenFields);
                }

                if (!\$frankenFields->has(\$__rptVar)) {
                    \$__rptCollection = _renderCmsField(\$__rptName, 'repeater', \$__rptOpts);
                    \$frankenFields[\$__rptVar] = \$__rptCollection;
                    view()->share('frankenFields', \$frankenFields);
                } else {
                    \$__rptCollection = \$frankenFields[\$__rptVar];
                }

                \$__rptIsEmpty = \$__rptCollection->isEmpty();
                ob_start();
                foreach (\$__rptCollection as \$__rptItem):
                    \$franken = \$__rptItem;
            ?>";
        });

        Blade::directive('endFrankenRepeater', function () {
            return <<<'PHP'
<?php
                endforeach;

                // Capture buffer content
                $__rptContent = ob_get_clean();

                // Check if has actual content
                $__rptHasContent = strlen(trim($__rptContent)) > 0;

                // Output content or placeholder
                if ($__rptHasContent) {
                    echo $__rptContent;
                } elseif ($__rptIsEmpty && $__rptShowPlaceholder) {
                    // Show placeholder for empty repeater
                    $__rptLabel = str_replace(['.', '_'], ' ', $__rptName);
                    $__rptLabel = ucwords($__rptLabel);
                    echo '<div style="display: inline-block; padding: 0.5rem 1rem; background: rgba(59, 130, 246, 0.05); border: 2px dashed rgba(59, 130, 246, 0.2); border-radius: 0.375rem; color: rgba(59, 130, 246, 0.6); font-size: 0.875rem;">';
                    echo '<svg style="display: inline-block; width: 1rem; height: 1rem; margin-right: 0.5rem; vertical-align: text-bottom;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>';
                    echo htmlspecialchars($__rptLabel) . ' (empty)';
                    echo '</div>';
                }

                unset($franken, $__rptItem, $__rptCollection, $__rptVar, $__rptName, $__rptOpts, $__rptMatches, $__rptShowPlaceholder, $__rptIsEmpty, $__rptContent, $__rptHasContent, $__rptLabel);
            ?>
PHP;
        });

        // Register tags directive - works both as loop and inline
        // Loop mode: @frankenTags('field') ... {{ $tag }} ... @endFrankenTags
        // Inline mode: @frankenTags('field', [], false) @endFrankenTags
        Blade::directive('frankenTags', function ($expression) use ($extractBalancedArray) {
            // Parse field name
            if (! preg_match('/^([\'"])(.*?)\1/', $expression, $nameMatch)) {
                return '<?php /* Invalid frankenTags syntax */ ?>';
            }
            $fieldName = $nameMatch[2];
            $after = substr($expression, strlen($nameMatch[0]));

            // Check for options array
            if (preg_match('/^\s*,\s*\[/', $after)) {
                $arrayStart = strpos($after, '[');
                $options = $extractBalancedArray($after, $arrayStart);
                $after = $options ? substr($after, $arrayStart + strlen($options)) : '';
            } else {
                $options = '[]';
            }

            // Check for placeholder boolean
            $placeholder = (preg_match('/^\s*,\s*(true|false)/', $after, $placeholderMatch)) ? $placeholderMatch[1] : 'true';

            return "<?php
                \$__tagName = '{$fieldName}';
                \$__tagOpts = {$options};
                \$__showPlaceholder = {$placeholder};
                \$__tagVar = cmsFieldVariableName(\$__tagName);

                if (!isset(\$frankenFields)) {
                    \$frankenFields = collect();
                    view()->share('frankenFields', \$frankenFields);
                }

                if (!\$frankenFields->has(\$__tagVar)) {
                    \$__tagCollection = _renderCmsField(\$__tagName, 'tags', \$__tagOpts);
                    \$frankenFields[\$__tagVar] = \$__tagCollection;
                    view()->share('frankenFields', \$frankenFields);
                } else {
                    \$__tagCollection = \$frankenFields[\$__tagVar];
                }

                // Start buffering to capture loop content
                \$__tagsArray = is_array(\$__tagCollection) ? \$__tagCollection : (\$__tagCollection ?? []);
                \$__tagsEmpty = empty(\$__tagsArray);
                ob_start();
                foreach (\$__tagsArray as \$__tagItem):
                    \$tag = \$__tagItem;
            ?>";
        });

        Blade::directive('endFrankenTags', function () {
            return <<<'PHP'
<?php
                endforeach;

                // Capture whatever was in the buffer
                $__tagContent = ob_get_clean();

                // Check if there's actual content (not just whitespace)
                $__hasActualContent = strlen(trim($__tagContent)) > 0;

                // If there's actual content, output it
                if ($__hasActualContent) {
                    echo $__tagContent;
                } elseif ($__tagsEmpty && $__showPlaceholder) {
                    // Empty array + placeholder enabled = show placeholder
                    $__fieldLabel = str_replace(['.', '_'], ' ', $__tagName);
                    $__fieldLabel = ucwords($__fieldLabel);
                    echo '<div style="display: inline-block; padding: 0.5rem 1rem; background: rgba(59, 130, 246, 0.05); border: 2px dashed rgba(59, 130, 246, 0.2); border-radius: 0.375rem; color: rgba(59, 130, 246, 0.6); font-size: 0.875rem;">';
                    echo '<svg style="display: inline-block; width: 1rem; height: 1rem; margin-right: 0.5rem; vertical-align: text-bottom;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>';
                    echo htmlspecialchars($__fieldLabel) . ' (empty)';
                    echo '</div>';
                }
                // Else: no output (inline mode or placeholder disabled)

                unset($tag, $__tagItem, $__tagsArray, $__tagsEmpty, $__tagContent, $__hasActualContent, $__showPlaceholder, $__fieldLabel, $__tagCollection, $__tagVar, $__tagName, $__tagOpts, $__tagParams);
            ?>
PHP;
        });
    }

    private function registerCmsFieldComposer(): void
    {
        // Register the composer for all views
        // The composer itself will check if it's a theme template
        View::composer('*', CmsFieldComposer::class);
    }

    private function registerStackInjection(): void
    {
        // Use a singleton to ensure stacks are only injected once per request
        $this->app->singleton('franken-cms.stacks-injected', function () {
            return false;
        });

        // Register a view composer to inject custom code stacks
        View::composer('*', function ($view) {
            // Only inject stacks for front-end views (not admin panel)
            if (request()->route() && str_starts_with(request()->route()->getName() ?? '', 'filament.')) {
                return;
            }

            // Guard against multiple injections per request
            if ($this->app->make('franken-cms.stacks-injected')) {
                return;
            }
            $this->app->instance('franken-cms.stacks-injected', true);

            try {
                $stackSettings = app(StackSettings::class);
                $stacksByName = $stackSettings->getEnabledStacksByName();

                foreach ($stacksByName as $stackName => $codeBlocks) {
                    foreach ($codeBlocks as $code) {
                        $view->getFactory()->startPush($stackName, $code . PHP_EOL);
                    }
                }
            } catch (Exception $e) {
                // Silently fail if settings aren't available yet
                // This can happen during installation or migrations
            }
        });
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
                            $published = Post::where('status', 'published')->count();
                            $draft = Post::where('status', 'draft')->count();
                            $pages = Post::where('type', 'page')->count();

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
                            $taxonomies = Taxonomy::count();
                            $terms = Term::count();

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
                            $menus = Menu::all();
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
                    'Custom Blade Directives' => '<fg=green;options=bold>@menu</>, <fg=green;options=bold>@frankenText</>, <fg=green;options=bold>@frankenRepeater</>',
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

    private function registerAiModal(): void
    {
        // Only register if Prism is installed
        if (! class_exists(Prism::class)) {
            return;
        }

        // Register the blog post wizard modal
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => view('franken-cms::filament.components.blog-post-wizard-wrapper')->render()
        );
    }

    private function registerBreadcrumbs(): void
    {
        // Check if breadcrumbs are enabled
        if (! config('franken-cms.breadcrumbs.enabled', true)) {
            return;
        }

        // Register automatic CMS breadcrumbs
        $breadcrumbService = $this->app->make(\FrankenCms\Services\BreadcrumbService::class);
        $breadcrumbService->registerBreadcrumbs();

        // Note: User-defined breadcrumbs from routes/breadcrumbs.php are automatically
        // loaded by the diglactic/laravel-breadcrumbs ServiceProvider, so we don't need
        // to load them here. Users can reference our breadcrumbs using:
        // $trail->parent('franken-cms.home') in their custom breadcrumb definitions.
    }
}
