<?php

namespace FrankenCms\Prompts;

use Exception;
use FrankenCms\Settings\AiSettings;

class PromptManager
{
    /**
     * Get prompt configuration by action key
     *
     *
     * @throws Exception
     */
    public function getPrompt(string $actionKey): array
    {
        $settings = app(AiSettings::class);

        // Check custom prompts first (from settings)
        $customPrompt = collect($settings->prompts ?? [])
            ->firstWhere('action_key', $actionKey);

        if ($customPrompt && ($customPrompt['enabled'] ?? false)) {
            return $customPrompt;
        }

        // Fall back to defaults
        $defaultPrompt = collect(DefaultPrompts::all())
            ->firstWhere('action_key', $actionKey);

        if ($defaultPrompt) {
            return $defaultPrompt;
        }

        throw new Exception("Prompt not found: {$actionKey}");
    }

    /**
     * Get all available prompts for a specific context
     */
    public function getPromptsForContext(string $context): array
    {
        $settings = app(AiSettings::class);

        // Merge default and custom prompts
        $allPrompts = array_merge(
            DefaultPrompts::all(),
            $settings->prompts ?? []
        );

        // Filter by context and enabled status
        return collect($allPrompts)
            ->filter(fn ($p) => $p['enabled'] ?? false)
            ->filter(fn ($p) => in_array($p['context'] ?? 'all', [$context, 'all']))
            ->unique('action_key')
            ->values()
            ->toArray();
    }

    /**
     * Format prompt template with variables
     */
    public function formatPrompt(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }
}
