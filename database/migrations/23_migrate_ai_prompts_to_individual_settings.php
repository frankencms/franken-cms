<?php

use FrankenCms\Prompts\DefaultPrompts;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Get the existing prompts array from the database if it exists
        $existingPromptsJson = \DB::table('settings')
            ->where('group', 'cms_ai')
            ->where('name', 'prompts')
            ->value('payload');

        $existingPrompts = [];
        if ($existingPromptsJson) {
            $existingPrompts = json_decode($existingPromptsJson, true) ?: [];
        }

        // Remove the old prompts array
        $this->migrator->delete('cms_ai.prompts');

        // Add new individual prompt settings with defaults
        // SEO Title Generator
        $seoTitlePrompt = $this->findPromptByActionKey($existingPrompts, 'generate_seo_title');
        $this->migrator->add('cms_ai.seo_title_enabled', $seoTitlePrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.seo_title_prompt', $seoTitlePrompt['prompt'] ?? DefaultPrompts::seoTitle());

        // SEO Meta Description
        $seoDescPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_seo_description');
        $this->migrator->add('cms_ai.seo_description_enabled', $seoDescPrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.seo_description_prompt', $seoDescPrompt['prompt'] ?? DefaultPrompts::seoDescription());

        // Post Teaser/Excerpt
        $teaserPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_teaser');
        $this->migrator->add('cms_ai.teaser_enabled', $teaserPrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.teaser_prompt', $teaserPrompt['prompt'] ?? DefaultPrompts::teaser());

        // Image Alt Text
        $altTextPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_alt_text');
        $this->migrator->add('cms_ai.alt_text_enabled', $altTextPrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.alt_text_prompt', $altTextPrompt['prompt'] ?? DefaultPrompts::altText());
    }

    public function down(): void
    {
        // Remove individual prompt settings
        $this->migrator->delete('cms_ai.seo_title_enabled');
        $this->migrator->delete('cms_ai.seo_title_prompt');
        $this->migrator->delete('cms_ai.seo_description_enabled');
        $this->migrator->delete('cms_ai.seo_description_prompt');
        $this->migrator->delete('cms_ai.teaser_enabled');
        $this->migrator->delete('cms_ai.teaser_prompt');
        $this->migrator->delete('cms_ai.alt_text_enabled');
        $this->migrator->delete('cms_ai.alt_text_prompt');

        // Add back the prompts array (empty)
        $this->migrator->add('cms_ai.prompts', []);
    }

    /**
     * Find a prompt by action key in the existing prompts array
     */
    protected function findPromptByActionKey(array $prompts, string $actionKey): array
    {
        foreach ($prompts as $prompt) {
            if (isset($prompt['action_key']) && $prompt['action_key'] === $actionKey) {
                return $prompt;
            }
        }

        return [];
    }
};
