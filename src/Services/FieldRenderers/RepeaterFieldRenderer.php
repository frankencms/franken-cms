<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\Collection;

class RepeaterFieldRenderer implements FieldRendererInterface
{
    /**
     * Render repeater field data
     *
     * Flattens items that have 'custom_fields' wrapper so templates can access fields directly
     */
    public function render(mixed $value, ?string $fieldName = null): Collection
    {
        $items = collect($value ?? []);

        // Flatten items that have 'custom_fields' wrapper
        return $items->map(function ($item) {
            if (is_array($item) && isset($item['custom_fields']) && is_array($item['custom_fields'])) {
                // Flatten: merge custom_fields into the root level
                $flattened = $item['custom_fields'];

                // Preserve any other keys that aren't custom_fields
                foreach ($item as $key => $value) {
                    if ($key !== 'custom_fields') {
                        $flattened[$key] = $value;
                    }
                }

                return $flattened;
            }

            return $item;
        });
    }
}
