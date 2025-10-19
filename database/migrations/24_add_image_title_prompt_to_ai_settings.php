<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_ai.image_title_enabled', true);
        $this->migrator->add('cms_ai.image_title_prompt', 'Generate a descriptive title for this image (hover text).

Additional Context:
Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Short and descriptive (3-8 words)
- Provides additional context when hovering
- Clear and informative
- Professional tone

Return only the image title.');
    }

    public function down(): void
    {
        $this->migrator->delete('cms_ai.image_title_enabled');
        $this->migrator->delete('cms_ai.image_title_prompt');
    }
};
