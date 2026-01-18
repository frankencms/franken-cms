<?php

declare(strict_types=1);

namespace FrankenCms\Directives\Providers;

use Illuminate\Support\Facades\Blade;

class SocialLinksDirectiveProvider
{
    /**
     * Register the social links Blade directives
     */
    public function register(): void
    {
        $this->registerSocialLinksDirective();
        $this->registerEndSocialLinksDirective();
    }

    /**
     * Register the @frankenSocialLinks directive
     *
     * Usage:
     *
     *   @frankenSocialLinks($authorBio)
     *       <a href="{{ $socialLink['url'] }}">{{ $socialLink['label'] }}</a>
     *
     *   @endFrankenSocialLinks
     *
     * Available $socialLink properties:
     *   - platform: The platform key (e.g., 'twitter', 'github')
     *   - value: The original value entered (username or full URL)
     *   - url: The resolved full URL
     *   - label: Human-readable platform name (e.g., 'Twitter / X')
     *   - icon: Blade Icons component name (e.g., 'fab-x-twitter')
     */
    protected function registerSocialLinksDirective(): void
    {
        Blade::directive('frankenSocialLinks', function ($expression) {
            return "<?php
                \$__socialLinksBio = {$expression};
                \$__socialLinksEmpty = true;
                \$__socialLinksItems = [];

                if (\$__socialLinksBio && method_exists(\$__socialLinksBio, 'getSocialLinks')) {
                    \$__socialLinksItems = \$__socialLinksBio->getSocialLinks();
                    \$__socialLinksEmpty = \$__socialLinksItems->isEmpty();
                }

                foreach (\$__socialLinksItems as \$__socialLinkData):
                    \$socialLink = \$__socialLinkData;
            ?>";
        });
    }

    /**
     * Register the @endFrankenSocialLinks directive
     */
    protected function registerEndSocialLinksDirective(): void
    {
        Blade::directive('endFrankenSocialLinks', function () {
            return '<?php
                endforeach;
                unset($socialLink, $__socialLinkData, $__socialLinksItems, $__socialLinksBio, $__socialLinksEmpty);
            ?>';
        });
    }
}
