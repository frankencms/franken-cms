<?php

namespace FrankenCms\Services;

use FrankenCms\Models\Page;
use FrankenCms\Support\LivewirePageRenderer;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Livewire\LivewireManager;

class TemplateResolver
{
    /**
     * Livewire namespace the theme folder is registered under
     */
    public const LIVEWIRE_NAMESPACE = 'theme';

    public static function resolve(Page $page): View | Response
    {

        // Get template from meta, default to 'default-template'
        $template = $page->template;

        $themeFolder = config('franken-cms.theme_folder');

        // Build the view path
        $view = sprintf('%s.%s', $themeFolder, $template);

        // TODO: Do we want to fallback to a default template od just 404?
        // maybe we can have a default Franken CMS Message Template Screen to show the error or what to do next
        // Check if the view exists, fallback to 'templates.default'
        if (! view()->exists($view)) {
            return view('franken-cms::help', [
                'message' => 'Make sure the template exists',
                'type'    => 'error', // Can be 'info', 'warning', 'error', or 'success'
            ]);
        }

        app(CurrentPageService::class)->setPage($page);

        return self::render($template, compact('page'));
    }

    /**
     * Render a theme template: as a Livewire page component when the template
     * is a single-file component, otherwise as a Blade view. A missing
     * template falls back to $fallback when given.
     *
     * @param  array<string, mixed>  $data
     */
    public static function render(string $template, array $data, ?string $fallback = null): View | Response
    {
        if (self::isLivewireTemplate($template)) {
            return LivewirePageRenderer::render(self::livewireName($template), $data);
        }

        $view = sprintf('%s.%s', config('franken-cms.theme_folder'), $template);

        if ($fallback !== null && $fallback !== $template && ! view()->exists($view)) {
            return self::render($fallback, $data);
        }

        return view($view, $data);
    }

    public static function isLivewireTemplate(?string $template): bool
    {
        if (! $template || ! config('franken-cms.livewire.page_components', true) || ! class_exists(LivewireManager::class)) {
            return false;
        }

        return app('livewire')->exists(self::livewireName($template));
    }

    public static function livewireName(string $template): string
    {
        return self::LIVEWIRE_NAMESPACE . '::' . $template;
    }
}
