<?php

namespace FrankenCms\Services;

use FrankenCms\Models\Page;
use Illuminate\View\View;

class TemplateResolver
{
    public static function resolve(Page $page): View
    {

        // Get template from meta, default to 'default-template'
        $template = $page->template;

        $templateFolder = config('franken-cms.template_folder');

        // Build the view path
        $view = sprintf('%s.%s', $templateFolder, $template);

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

        return view($view, compact('page'));
    }
}
