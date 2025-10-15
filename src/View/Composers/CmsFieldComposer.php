<?php

namespace FrankenCms\View\Composers;

use FrankenCms\Services\TemplateFieldParser;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class CmsFieldComposer
{
    public function __construct(
        protected TemplateFieldParser $parser
    ) {}

    /**
     * Bind data to the view.
     * Pre-populates all CMS fields before template rendering starts.
     */
    public function compose(View $view): void
    {
        // Get the view path
        $viewPath = $view->getPath();

        // Only process if this is a theme template
        if (! $this->isThemeTemplate($viewPath)) {
            return;
        }

        // Parse the template to find all @cmsField directives
        $fields = $this->parser->parseTemplate($viewPath);

        if (empty($fields)) {
            return;
        }

        // Get existing $cmsFields collection or create a new one
        $cmsFields = ViewFacade::shared('cmsFields') ?? collect();

        // Pre-populate all fields
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            $options = $field['options'] ?? [];

            // Generate the camelCase key
            $varName = cmsFieldVariableName($fieldName);

            // Only populate if not already set
            if (! $cmsFields->has($varName)) {
                // Render the field value
                $value = _renderCmsField($fieldName, $fieldType, $options);
                $cmsFields[$varName] = $value;
            }
        }

        // Share the collection with all views
        ViewFacade::share('cmsFields', $cmsFields);

        // Also pass it to this specific view
        $view->with('cmsFields', $cmsFields);
    }

    /**
     * Check if this is a theme template that should be processed
     */
    protected function isThemeTemplate(string $path): bool
    {
        $themeFolder = config('franken-cms.theme_folder', 'theme');
        $themePath = resource_path("views/{$themeFolder}");

        return str_starts_with($path, $themePath);
    }
}
