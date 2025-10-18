<?php

namespace FrankenCms\Settings;

use FrankenCms\SettingsCasts\EncryptedSettingsCast;
use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    // Provider Configuration
    public bool $enabled = false;

    public string $provider = 'openai';

    public ?string $api_key = null;

    public string $model = 'gpt-4o';

    // Prompt Templates (default + custom)
    public array $prompts = [];

    public static function group(): string
    {
        return 'cms_ai';
    }

    /**
     * Define encrypted fields
     */
    public static function casts(): array
    {
        return [
            'api_key' => EncryptedSettingsCast::class,
        ];
    }
}
