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

        // Remove the old prompts array if it exists
        if ($existingPromptsJson !== null) {
            $this->migrator->delete('cms_ai.prompts');
        }

        // Add new individual prompt settings with defaults (only if they don't exist)
        // SEO Title Generator
        $seoTitlePrompt = $this->findPromptByActionKey($existingPrompts, 'generate_seo_title');
        $this->addIfNotExists('cms_ai.seo_title_enabled', $seoTitlePrompt['enabled'] ?? true);
        $this->addIfNotExists('cms_ai.seo_title_prompt', $seoTitlePrompt['prompt'] ?? DefaultPrompts::seoTitle());

        // SEO Meta Description
        $seoDescPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_seo_description');
        $this->addIfNotExists('cms_ai.seo_description_enabled', $seoDescPrompt['enabled'] ?? true);
        $this->addIfNotExists('cms_ai.seo_description_prompt', $seoDescPrompt['prompt'] ?? DefaultPrompts::seoDescription());

        // Post Teaser/Excerpt
        $teaserPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_teaser');
        $this->addIfNotExists('cms_ai.teaser_enabled', $teaserPrompt['enabled'] ?? true);
        $this->addIfNotExists('cms_ai.teaser_prompt', $teaserPrompt['prompt'] ?? DefaultPrompts::teaser());

        // Image Alt Text
        $altTextPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_alt_text');
        $this->addIfNotExists('cms_ai.alt_text_enabled', $altTextPrompt['enabled'] ?? true);
        $this->addIfNotExists('cms_ai.alt_text_prompt', $altTextPrompt['prompt'] ?? DefaultPrompts::altText());
    }

    public function down(): void
    {
        // Remove individual prompt settings if they exist
        $settingsToDelete = [
            'cms_ai.seo_title_enabled',
            'cms_ai.seo_title_prompt',
            'cms_ai.seo_description_enabled',
            'cms_ai.seo_description_prompt',
            'cms_ai.teaser_enabled',
            'cms_ai.teaser_prompt',
            'cms_ai.alt_text_enabled',
            'cms_ai.alt_text_prompt',
        ];

        foreach ($settingsToDelete as $settingKey) {
            if (\DB::table('settings')->where('group', 'cms_ai')->where('name', str_replace('cms_ai.', '', $settingKey))->exists()) {
                $this->migrator->delete($settingKey);
            }
        }

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

    /**
     * Add a setting only if it doesn't already exist
     */
    protected function addIfNotExists(string $key, $value): void
    {
        [$group, $name] = explode('.', $key);

        $exists = \DB::table('settings')
            ->where('group', $group)
            ->where('name', $name)
            ->exists();

        if (! $exists) {
            $this->migrator->add($key, $value);
        }
    }
};
