<?php

namespace FrankenCms\Support;

use Illuminate\Http\Response;
use Livewire\Features\SupportPageComponents\PageComponentConfig;
use Livewire\Features\SupportPageComponents\SupportPageComponents;
use ReflectionClass;
use ReflectionProperty;

/**
 * Renders a Livewire component as a full page for CMS-resolved content.
 *
 * Mirrors Livewire's HandlesPageComponents::__invoke (v4), except the mount
 * params come from the CMS (page/post/term) instead of route parameters,
 * since the fallback route has none to bind.
 */
class LivewirePageRenderer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function render(string $name, array $data = []): Response
    {
        $params = self::acceptedParams($name, $data);
        $html = null;

        $originalLayout = config('livewire.component_layout');

        if ($layout = self::defaultLayout()) {
            config(['livewire.component_layout' => $layout]);
        }

        try {
            $layoutConfig = SupportPageComponents::interceptTheRenderOfTheComponentAndRetreiveTheLayoutConfiguration(function () use (&$html, $name, $params) {
                $html = app('livewire')->mount($name, $params);
            });

            $layoutConfig = $layoutConfig ?: new PageComponentConfig;
        } finally {
            config(['livewire.component_layout' => $originalLayout]);
        }

        $layoutConfig->normalizeViewNameAndParamsForBladeComponents();

        $response = response(SupportPageComponents::renderContentsIntoLayout($html, $layoutConfig));

        if (is_callable($layoutConfig->response)) {
            call_user_func($layoutConfig->response, $response);
        }

        return $response;
    }

    /**
     * Only pass data the component declares as a public property or mount()
     * argument; Livewire would otherwise treat the rest as HTML attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function acceptedParams(string $name, array $data): array
    {
        $reflection = new ReflectionClass(app('livewire')->new($name));

        $accepted = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
            ->reject(fn (ReflectionProperty $property) => $property->isStatic())
            ->map(fn (ReflectionProperty $property) => $property->getName());

        if ($reflection->hasMethod('mount')) {
            $accepted = $accepted->merge(collect($reflection->getMethod('mount')->getParameters())->map->getName());
        }

        return array_intersect_key($data, array_flip($accepted->all()));
    }

    protected static function defaultLayout(): ?string
    {
        if ($layout = config('franken-cms.livewire.layout')) {
            return $layout;
        }

        // The stub theme's layout component, when present
        $themeLayout = sprintf('%s.components.layouts.main.index', config('franken-cms.theme_folder', 'theme'));

        return view()->exists($themeLayout) ? $themeLayout : null;
    }
}
