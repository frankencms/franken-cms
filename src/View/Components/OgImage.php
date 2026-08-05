<?php

namespace FrankenCms\View\Components;

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\OgImage\OgImageFeature;
use FrankenCms\Services\CurrentPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

class OgImage extends Component
{
    /**
     * Returns HtmlString('') instead of '' when nothing resolves: an empty
     * string return compiles into a zero-byte cached view that the framework
     * rewrites on every request, which sends the Vite dev server into an
     * infinite full-reload loop.
     */
    public function render(): View | HtmlString
    {
        $post = app(CurrentPageService::class)->getPage();

        if (! OgImageFeature::resolvesFor($post)) {
            return new HtmlString('');
        }

        if ($url = $this->manualUrl($post)) {
            return view('franken-cms::components.og-image', [
                'template' => null,
                'url'      => $url,
                'post'     => $post,
            ]);
        }

        if ($template = OgImageFeature::templateFor($post)) {
            return view('franken-cms::components.og-image', [
                'template' => $template,
                'url'      => null,
                'post'     => $post,
            ]);
        }

        if ($url = $this->defaultUrl()) {
            return view('franken-cms::components.og-image', [
                'template' => null,
                'url'      => $url,
                'post'     => $post,
            ]);
        }

        if ($template = OgImageFeature::defaultTemplate()) {
            return view('franken-cms::components.og-image', [
                'template' => $template,
                'url'      => null,
                'post'     => $post,
            ]);
        }

        return new HtmlString('');
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
