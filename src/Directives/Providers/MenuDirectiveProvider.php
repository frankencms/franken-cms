<?php

namespace FrankenCms\Directives\Providers;

use Illuminate\Support\Facades\Blade;

class MenuDirectiveProvider
{
    /**
     * Register the menu Blade directives
     */
    public function register(): void
    {
        $this->registerMenuDirective();
        $this->registerEndMenuDirective();
    }

    /**
     * Register the @frankenMenu directive
     */
    protected function registerMenuDirective(): void
    {
        Blade::directive('frankenMenu', function ($expression) {
            // Parse menu slug/location
            if (! preg_match('/^([\'"])(.*?)\1/', $expression, $nameMatch)) {
                return '<?php /* Invalid frankenMenu syntax */ ?>';
            }
            $menuSlug = $nameMatch[2];
            $after = substr($expression, strlen($nameMatch[0]));

            // Check for options array
            if (preg_match('/^\s*,\s*\[/', $after)) {
                $arrayStart = strpos($after, '[');
                $options = $this->extractBalancedArray($after, $arrayStart);
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
    }

    /**
     * Register the @endFrankenMenu directive
     */
    protected function registerEndMenuDirective(): void
    {
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

    /**
     * Extract a balanced array from a string starting at a given position
     */
    protected function extractBalancedArray(string $str, int $start): ?string
    {
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
    }
}
