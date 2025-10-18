<?php

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class StackSettings extends Settings
{
    /**
     * Array of stack configurations
     *
     * Each stack entry contains:
     * - label: Descriptive name for the admin UI
     * - stack_name: The Laravel stack name used in @stack() directive
     * - code: The actual code/script to inject
     * - enabled: Whether this stack is currently active
     */
    public array $stacks = [];

    public static function group(): string
    {
        return 'cms_stacks';
    }

    /**
     * Get all enabled stacks grouped by stack name
     *
     * @return array
     */
    public function getEnabledStacksByName(): array
    {
        $grouped = [];

        foreach ($this->stacks as $stack) {
            if (($stack['enabled'] ?? false) && !empty($stack['code'] ?? '')) {
                $stackName = $stack['stack_name'] ?? '';
                if (!isset($grouped[$stackName])) {
                    $grouped[$stackName] = [];
                }
                $grouped[$stackName][] = $stack['code'];
            }
        }

        return $grouped;
    }
}
