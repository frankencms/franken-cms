<?php

namespace FrankenCms\View\Components;

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\OgImage\OgImageFeature;
use FrankenCms\Services\CurrentPageService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OgImage extends Component
{
    public function render(): View | string
    {
        if (! OgImageFeature::isEnabled()) {
            return '';
        }

        $post = app(CurrentPageService::class)->getPage();

        if ($template = OgImageFeature::templateFor($post)) {
            return view('franken-cms::components.og-image', [
                'template' => $template,
                'url'      => null,
                'post'     => $post,
            ]);
        }

        if ($url = $this->manualUrl($post) ?? $this->defaultUrl()) {
            return view('franken-cms::components.og-image', [
                'template' => null,
                'url'      => $url,
                'post'     => $post,
            ]);
        }

        return '';
    }

    protected function manualUrl(?Post $post): ?string
    {
        return $post?->getFirstMedia('seo-og')?->getFullUrl('og');
    }

    protected function defaultUrl(): ?string
    {
        $siteMedia = SiteSettingsMedia::getInstance();

        if ($siteMedia->hasMedia('og-default')) {
            return $siteMedia->getFirstMedia('og-default')?->getFullUrl('og');
        }

        return null;
    }
}
