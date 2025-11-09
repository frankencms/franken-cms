<?php

namespace FrankenCms\View\Composers;

use FrankenCms\Services\TemplateFieldExtractor;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class CmsFieldComposer
{
    protected static array $parsedFields = [];
    protected static array $fileTimestamps = [];

    public function __construct(
        protected TemplateFieldExtractor $parser
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

        // Check if caching is enabled
        $cacheEnabled = config('franken-cms.cache_parsed_fields', true);

        if ($cacheEnabled) {
            // Use static in-memory cache with file modification time tracking
            // This works safely in both traditional PHP and Octane/FrankenPHP
            $currentMtime = filemtime($viewPath);

            // Invalidate cache if file changed or not yet cached
            if (! isset(static::$fileTimestamps[$viewPath]) ||
                static::$fileTimestamps[$viewPath] !== $currentMtime) {
                static::$parsedFields[$viewPath] = $this->parser->parseTemplate($viewPath);
                static::$fileTimestamps[$viewPath] = $currentMtime;
            }

            $fields = static::$parsedFields[$viewPath];
        } else {
            // No caching - parse template on every request (useful for development)
            $fields = $this->parser->parseTemplate($viewPath);
        }

        if (empty($fields)) {
            return;
        }

        // Get existing $frankenFields collection or create a new one
        $frankenFields = ViewFacade::shared('frankenFields') ?? collect();

        // Pre-populate all fields
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            $options = $field['options'] ?? [];

            // Generate the camelCase key
            $varName = cmsFieldVariableName($fieldName);

            // Only populate if not already set
            if (! $frankenFields->has($varName)) {
                // Render the field value
                $value = _renderCmsField($fieldName, $fieldType, $options);
                $frankenFields[$varName] = $value;
            }
        }

        // Share the collection with all views
        ViewFacade::share('frankenFields', $frankenFields);

        // Also pass it to this specific view
        $view->with('frankenFields', $frankenFields);
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
