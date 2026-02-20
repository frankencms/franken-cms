<?php

namespace FrankenCms\Services;

class DirectiveRenderer
{
    /**
     * Generate the PHP code for a standard inline field directive
     */
    public function renderInlineDirective(string $fieldType): string
    {
        return <<<'PHP'
<?php
                $__fParams = _parseFieldExpression($expression);
                $__fName = $__fParams['name'];
                $__fOpts = $__fParams['options'];
                $__fVar = frankenFieldVariableName($__fName);

                if (!isset($frankenFields)) {
                    $frankenFields = collect();
                    view()->share('frankenFields', $frankenFields);
                }

                if (!$frankenFields->has($__fVar)) {
                    $__fValue = _renderFrankenField($__fName, '{$fieldType}', $__fOpts);
                    $frankenFields[$__fVar] = $__fValue;
                    view()->share('frankenFields', $frankenFields);
                } else {
                    $__fValue = $frankenFields->get($__fVar);
                }

                echo $__fValue;
                unset($__fParams, $__fName, $__fOpts, $__fVar, $__fValue);
            ?>
PHP;
    }

    /**
     * Generate the opening PHP code for a block directive
     */
    public function renderBlockDirectiveOpen(string $fieldType): string
    {
        return <<<'PHP'
<?php
                $__fParams = _parseFieldExpression($expression);
                $__fName = $__fParams['name'];
                $__fOpts = $__fParams['options'];
                $__fVar = frankenFieldVariableName($__fName);
                $__showPlaceholder = $__fParams['placeholder'] ?? true;

                if (!isset($frankenFields)) {
                    $frankenFields = collect();
                    view()->share('frankenFields', $frankenFields);
                }

                if (!$frankenFields->has($__fVar)) {
                    $__fValue = _renderFrankenField($__fName, '{$fieldType}', $__fOpts);
                    $frankenFields[$__fVar] = $__fValue;
                    view()->share('frankenFields', $frankenFields);
                } else {
                    $__fValue = $frankenFields->get($__fVar);
                }

                $__fHasContent = !empty($__fValue);
                if ($__fHasContent || $__showPlaceholder) {
                    echo $__fValue;
                } else {
            ?>
PHP;
    }

    /**
     * Generate the closing PHP code for a block directive
     */
    public function renderBlockDirectiveClose(): string
    {
        return <<<'PHP'
<?php
                }
                unset($__fParams, $__fName, $__fOpts, $__fVar, $__fValue, $__fHasContent, $__showPlaceholder);
            ?>
PHP;
    }

    /**
     * Generate the PHP code for the repeater block directive opening
     */
    public function renderRepeaterDirectiveOpen(): string
    {
        return <<<'PHP'
<?php
                // Extract arguments from array
                $__rName = $__repeaterExpression[0] ?? '';
                $__rOpts = $__repeaterExpression[1] ?? [];
                $__showPlaceholder = $__repeaterExpression[2] ?? true;
                $__rVar = frankenFieldVariableName($__rName);

                if (!isset($frankenFields)) {
                    $frankenFields = collect();
                    view()->share('frankenFields', $frankenFields);
                }

                if (!$frankenFields->has($__rVar)) {
                    $__rValue = _renderFrankenField($__rName, 'repeater', $__rOpts);
                    $frankenFields[$__rVar] = $__rValue;
                    view()->share('frankenFields', $frankenFields);
                } else {
                    $__rValue = $frankenFields->get($__rVar);
                }

                $__rItems = $__rValue ?? [];
                $__rHasItems = !empty($__rItems);

                if ($__rHasItems) {
                    foreach ($__rItems as $__rIndex => $item) {
            ?>
PHP;
    }

    /**
     * Generate the PHP code for the repeater block directive closing
     */
    public function renderRepeaterDirectiveClose(): string
    {
        return <<<'PHP'
<?php
                    }
                } elseif ($__showPlaceholder) {
                    echo '<div class="cms-placeholder" data-cms-field="' . e($__rName) . '" data-cms-type="repeater">';
                    echo 'No items in repeater: ' . e($__rName);
                    echo '</div>';
                }
                unset($__rName, $__rOpts, $__rVar, $__rValue, $__rItems, $__rHasItems, $__rIndex, $item, $__showPlaceholder);
            ?>
PHP;
    }

    /**
     * Generate the PHP code for the tags block directive opening
     */
    public function renderTagsDirectiveOpen(): string
    {
        return <<<'PHP'
<?php
                // Extract arguments from array
                $__tName = $__tagsExpression[0] ?? '';
                $__tOpts = $__tagsExpression[1] ?? [];
                $__showPlaceholder = $__tagsExpression[2] ?? true;
                $__tVar = frankenFieldVariableName($__tName);

                if (!isset($frankenFields)) {
                    $frankenFields = collect();
                    view()->share('frankenFields', $frankenFields);
                }

                if (!$frankenFields->has($__tVar)) {
                    $__tValue = _renderFrankenField($__tName, 'tags', $__tOpts);
                    $frankenFields[$__tVar] = $__tValue;
                    view()->share('frankenFields', $frankenFields);
                } else {
                    $__tValue = $frankenFields->get($__tVar);
                }

                $__tItems = $__tValue ?? [];
                $__tHasTags = !empty($__tItems);

                if ($__tHasTags) {
                    foreach ($__tItems as $__tIndex => $tag) {
            ?>
PHP;
    }

    /**
     * Generate the PHP code for the tags block directive closing
     */
    public function renderTagsDirectiveClose(): string
    {
        return <<<'PHP'
<?php
                    }
                } elseif ($__showPlaceholder) {
                    echo '<div class="cms-placeholder" data-cms-field="' . e($__tName) . '" data-cms-type="tags">';
                    echo 'No tags for: ' . e($__tName);
                    echo '</div>';
                }
                unset($__tName, $__tOpts, $__tVar, $__tValue, $__tItems, $__tHasTags, $__tIndex, $tag, $__showPlaceholder);
            ?>
PHP;
    }
}
