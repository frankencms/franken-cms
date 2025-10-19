<?php

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
        $this->migrator->add('cms_ai.seo_title_prompt', $seoTitlePrompt['prompt'] ?? 'Generate an SEO-optimized title (50-60 characters) for a blog post.

Title: {title}
Content: {content}

Requirements:
- Must be 50-60 characters
- Include target keywords naturally
- Compelling and click-worthy
- Clear and descriptive

Return only the SEO title, nothing else.');

        // SEO Meta Description
        $seoDescPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_seo_description');
        $this->migrator->add('cms_ai.seo_description_enabled', $seoDescPrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.seo_description_prompt', $seoDescPrompt['prompt'] ?? 'Generate an SEO meta description (150-160 characters) for:

Title: {title}
Content: {content}

Requirements:
- Must be 150-160 characters
- Summarize main points
- Include call-to-action
- Use active voice

Return only the meta description, nothing else.');

        // Post Teaser/Excerpt
        $teaserPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_teaser');
        $this->migrator->add('cms_ai.teaser_enabled', $teaserPrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.teaser_prompt', $teaserPrompt['prompt'] ?? 'Create a compelling teaser/excerpt (2-3 sentences, ~150 characters) for this blog post:

{content}

Requirements:
- Hook the reader
- Summarize key value
- Create curiosity
- 2-3 sentences maximum

Return only the teaser.');

        // Image Alt Text
        $altTextPrompt = $this->findPromptByActionKey($existingPrompts, 'generate_alt_text');
        $this->migrator->add('cms_ai.alt_text_enabled', $altTextPrompt['enabled'] ?? true);
        $this->migrator->add('cms_ai.alt_text_prompt', $altTextPrompt['prompt'] ?? 'Analyze this image and generate descriptive alt text for accessibility.

Additional Context:
Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Maximum 125 characters
- Describe what you see in the image
- Include relevant details for accessibility
- Be specific and descriptive

Return only the alt text.');
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
