<?php

declare(strict_types=1);

namespace FrankenCms\Providers;

use FrankenCms\Services\SeoService;
use FrankenCms\Settings\SeoSettings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use romanzipp\Seo\Builders\StructBuilder;
use romanzipp\Seo\Facades\Seo;
use romanzipp\Seo\Services\SeoService as RomanzippSeoService;
use romanzipp\Seo\Structs\Meta;
use romanzipp\Seo\Structs\Title;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the SeoService singleton
        $this->app->singleton(SeoService::class, function ($app) {
            return new SeoService($app->make(SeoSettings::class));
        });
    }

    public function boot(): void
    {
        // Configure struct builder indentation
        StructBuilder::$indent = str_repeat(' ', 4);

        // Add a getTitle method for obtaining the unmodified title
        Seo::macro('getTitle', function () {
            /** @var RomanzippSeoService $this */
            if (! $title = $this->getStruct(Title::class)) {
                return null;
            }

            if (! $body = $title->getBody()) {
                return null;
            }

            return $body->getOriginalData();
        });

        // Add custom tag macro
        Seo::macro('customTag', fn (string $value) => /** @var RomanzippSeoService $this */
            $this->add(
                Meta::make()->name('custom')->content($value)
            ));

        // Register Blade directives for SEO
        $this->registerBladeDirectives();
    }

    /**
     * Register SEO-related Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('seoTitle', fn ($expression): string => "<?php seo()->title({$expression}); ?>");

        Blade::directive('seoDescription', fn ($expression): string => "<?php seo()->description({$expression}); ?>");

        Blade::directive('seoRobots', fn ($expression): string => "<?php seo()->meta('robots', {$expression}); ?>");

        Blade::directive('seoCanonical', fn ($expression): string => "<?php seo()->canonical({$expression}); ?>");

        Blade::directive('seoImage', fn ($expression): string => "<?php seo()->image({$expression}); ?>");

        // Additional helper directives using our SeoService
        Blade::directive('seoTitleFor', function ($expression) {
            return "<?php echo app(\FrankenCms\Services\SeoService::class)->getTitle({$expression}); ?>";
        });

        Blade::directive('seoDescriptionFor', function ($expression) {
            return "<?php echo app(\FrankenCms\Services\SeoService::class)->getDescription({$expression}); ?>";
        });
    }
}
