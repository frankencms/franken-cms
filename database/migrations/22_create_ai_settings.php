<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_ai.enabled', false);
        $this->migrator->add('cms_ai.provider', 'openai');
        $this->migrator->add('cms_ai.api_key', null);
        $this->migrator->add('cms_ai.model', 'gpt-4o');
        $this->migrator->add('cms_ai.prompts', []);
    }

    public function down(): void
    {
        $this->migrator->delete('cms_ai.enabled');
        $this->migrator->delete('cms_ai.provider');
        $this->migrator->delete('cms_ai.api_key');
        $this->migrator->delete('cms_ai.model');
        $this->migrator->delete('cms_ai.prompts');
    }
};
