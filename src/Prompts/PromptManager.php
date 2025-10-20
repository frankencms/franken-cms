<?php

namespace FrankenCms\Prompts;

use Exception;
use FrankenCms\Settings\AiSettings;

class PromptManager
{
    /**
     * Map action keys to settings properties
     */
    protected const PROMPT_MAP = [
        'generate_seo_title' => [
            'enabled_key'     => 'seo_title_enabled',
            'prompt_key'      => 'seo_title_prompt',
            'context'         => 'all',
            'supports_vision' => false,
            'max_tokens'      => 100,  // ~60 chars for SEO title
        ],
        'generate_seo_description' => [
            'enabled_key'     => 'seo_description_enabled',
            'prompt_key'      => 'seo_description_prompt',
            'context'         => 'all',
            'supports_vision' => false,
            'max_tokens'      => 250,  // ~160 chars for meta description
        ],
        'generate_teaser' => [
            'enabled_key'     => 'teaser_enabled',
            'prompt_key'      => 'teaser_prompt',
            'context'         => 'post',
            'supports_vision' => false,
            'max_tokens'      => 300,  // ~100-200 words
        ],
        'generate_alt_text' => [
            'enabled_key'     => 'alt_text_enabled',
            'prompt_key'      => 'alt_text_prompt',
            'context'         => 'media',
            'supports_vision' => true,
            'max_tokens'      => 150,  // ~100 chars for alt text
        ],
        'generate_image_title' => [
            'enabled_key'     => 'image_title_enabled',
            'prompt_key'      => 'image_title_prompt',
            'context'         => 'media',
            'supports_vision' => true,
            'max_tokens'      => 100,  // ~60 chars for title
        ],
        'generate_blog_post' => [
            'enabled_key'     => 'blog_post_enabled',
            'prompt_key'      => 'blog_post_prompt',
            'context'         => 'post',
            'supports_vision' => false,
            'max_tokens'      => 3000,  // ~800-1200 words = ~2000-2400 tokens, plus buffer
        ],
        'blog_post_title' => [
            'enabled_key'     => 'blog_post_title_enabled',
            'prompt_key'      => 'blog_post_title_prompt',
            'context'         => 'post',
            'supports_vision' => false,
            'max_tokens'      => 100,  // ~50-60 chars for blog post title
        ],
    ];

    /**
     * Get prompt configuration by action key
     *
     * @throws Exception
     */
    public function getPrompt(string $actionKey): array
    {
        if (! isset(self::PROMPT_MAP[$actionKey])) {
            throw new Exception("Prompt not found: {$actionKey}");
        }

        $settings = app(AiSettings::class);
        $config = self::PROMPT_MAP[$actionKey];

        $enabledKey = $config['enabled_key'];
        $promptKey = $config['prompt_key'];

        if (! $settings->$enabledKey) {
            throw new Exception("Prompt is disabled: {$actionKey}");
        }

        return [
            'action_key'      => $actionKey,
            'prompt'          => $settings->$promptKey,
            'enabled'         => $settings->$enabledKey,
            'context'         => $config['context'],
            'supports_vision' => $config['supports_vision'] ?? false,
            'max_tokens'      => $config['max_tokens'] ?? 500,
        ];
    }

    /**
     * Get all available prompts for a specific context
     */
    public function getPromptsForContext(string $context): array
    {
        $settings = app(AiSettings::class);
        $prompts = [];

        foreach (self::PROMPT_MAP as $actionKey => $config) {
            $enabledKey = $config['enabled_key'];
            $promptKey = $config['prompt_key'];

            // Check if enabled and matches context
            if ($settings->$enabledKey && in_array($config['context'], [$context, 'all'])) {
                $prompts[] = [
                    'action_key'      => $actionKey,
                    'prompt'          => $settings->$promptKey,
                    'enabled'         => $settings->$enabledKey,
                    'context'         => $config['context'],
                    'supports_vision' => $config['supports_vision'] ?? false,
                    'max_tokens'      => $config['max_tokens'] ?? 500,
                ];
            }
        }

        return $prompts;
    }

    /**
     * Format prompt template with variables
     */
    public function formatPrompt(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Convert value to string - handle arrays, nulls, etc.
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_null($value)) {
                $value = '';
            } elseif (! is_string($value)) {
                $value = (string) $value;
            }

            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }
}
