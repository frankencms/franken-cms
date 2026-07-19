<?php

use FrankenCms\Prompts\PromptManager;
use FrankenCms\Settings\AiSettings;
use Spatie\LaravelSettings\Settings;

/**
 * Create a mock AiSettings with proper Spatie initialization.
 */
function createAiSettingsMock(array $overrides = []): AiSettings
{
    $settings = Mockery::mock(AiSettings::class)->makePartial();

    $reflection = new ReflectionProperty(Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    // Set defaults
    $settings->enabled = $overrides['enabled'] ?? true;
    $settings->text_provider = $overrides['provider'] ?? 'openai';
    $settings->text_model = $overrides['model'] ?? 'gpt-4o';
    $settings->seo_title_enabled = $overrides['seo_title_enabled'] ?? true;
    $settings->seo_title_prompt = $overrides['seo_title_prompt'] ?? 'Generate an SEO title for: {title}';
    $settings->seo_description_enabled = $overrides['seo_description_enabled'] ?? true;
    $settings->seo_description_prompt = $overrides['seo_description_prompt'] ?? 'Generate meta description for: {title}';
    $settings->teaser_enabled = $overrides['teaser_enabled'] ?? true;
    $settings->teaser_prompt = $overrides['teaser_prompt'] ?? 'Generate teaser for: {content}';
    $settings->alt_text_enabled = $overrides['alt_text_enabled'] ?? true;
    $settings->alt_text_prompt = $overrides['alt_text_prompt'] ?? 'Generate alt text for image: {filename}';
    $settings->image_title_enabled = $overrides['image_title_enabled'] ?? true;
    $settings->image_title_prompt = $overrides['image_title_prompt'] ?? 'Generate title for image: {filename}';
    $settings->blog_post_enabled = $overrides['blog_post_enabled'] ?? true;
    $settings->blog_post_prompt = $overrides['blog_post_prompt'] ?? 'Write a blog post about: {title}';
    $settings->blog_post_title_enabled = $overrides['blog_post_title_enabled'] ?? true;
    $settings->blog_post_title_prompt = $overrides['blog_post_title_prompt'] ?? 'Generate blog post title for: {title}';

    return $settings;
}

beforeEach(function () {
    $this->settings = createAiSettingsMock();
    app()->instance(AiSettings::class, $this->settings);
    $this->manager = new PromptManager;
});

describe('getPrompt', function () {
    test('returns prompt config for valid action key', function () {
        $config = $this->manager->getPrompt('generate_seo_title');

        expect($config)->toBeArray()
            ->toHaveKeys(['action_key', 'prompt', 'enabled', 'context', 'supports_vision', 'max_tokens']);
        expect($config['action_key'])->toBe('generate_seo_title');
        expect($config['enabled'])->toBeTrue();
        expect($config['context'])->toBe('all');
        expect($config['supports_vision'])->toBeFalse();
        expect($config['max_tokens'])->toBe(100);
    });

    test('returns vision-enabled config for alt text', function () {
        $config = $this->manager->getPrompt('generate_alt_text');

        expect($config['supports_vision'])->toBeTrue();
        expect($config['context'])->toBe('media');
    });

    test('returns vision-enabled config for image title', function () {
        $config = $this->manager->getPrompt('generate_image_title');

        expect($config['supports_vision'])->toBeTrue();
    });

    test('returns high max_tokens for blog post', function () {
        $config = $this->manager->getPrompt('generate_blog_post');

        expect($config['max_tokens'])->toBe(3000);
    });

    test('throws exception for invalid action key', function () {
        $this->manager->getPrompt('nonexistent_action');
    })->throws(Exception::class, 'Prompt not found: nonexistent_action');

    test('throws exception when prompt is disabled', function () {
        $this->settings->seo_title_enabled = false;

        $this->manager->getPrompt('generate_seo_title');
    })->throws(Exception::class, 'Prompt is disabled: generate_seo_title');

    test('includes prompt template from settings', function () {
        $config = $this->manager->getPrompt('generate_seo_title');

        expect($config['prompt'])->toBe('Generate an SEO title for: {title}');
    });
});

describe('getPromptsForContext', function () {
    test('returns post context prompts', function () {
        $prompts = $this->manager->getPromptsForContext('post');
        $actionKeys = array_column($prompts, 'action_key');

        // Post context should include post-specific + "all" context prompts
        expect($actionKeys)->toContain('generate_teaser');
        expect($actionKeys)->toContain('generate_blog_post');
        expect($actionKeys)->toContain('blog_post_title');
        expect($actionKeys)->toContain('generate_seo_title');       // "all" context
        expect($actionKeys)->toContain('generate_seo_description'); // "all" context
    });

    test('returns media context prompts', function () {
        $prompts = $this->manager->getPromptsForContext('media');
        $actionKeys = array_column($prompts, 'action_key');

        expect($actionKeys)->toContain('generate_alt_text');
        expect($actionKeys)->toContain('generate_image_title');
        expect($actionKeys)->toContain('generate_seo_title');       // "all" context
    });

    test('excludes disabled prompts', function () {
        $this->settings->teaser_enabled = false;

        $prompts = $this->manager->getPromptsForContext('post');
        $actionKeys = array_column($prompts, 'action_key');

        expect($actionKeys)->not->toContain('generate_teaser');
    });

    test('returns empty array for unknown context', function () {
        $prompts = $this->manager->getPromptsForContext('nonexistent');

        // Should only contain "all" context prompts
        foreach ($prompts as $prompt) {
            expect($prompt['context'])->toBe('all');
        }
    });
});

describe('formatPrompt', function () {
    test('replaces single placeholder', function () {
        $result = $this->manager->formatPrompt('Hello {name}!', ['name' => 'Igor']);

        expect($result)->toBe('Hello Igor!');
    });

    test('replaces multiple placeholders', function () {
        $result = $this->manager->formatPrompt(
            'Generate SEO title for "{title}" with content: {content}',
            ['title' => 'My Post', 'content' => 'Some content']
        );

        expect($result)->toBe('Generate SEO title for "My Post" with content: Some content');
    });

    test('handles null values as empty string', function () {
        $result = $this->manager->formatPrompt('Value: {value}', ['value' => null]);

        expect($result)->toBe('Value: ');
    });

    test('handles array values as JSON', function () {
        $result = $this->manager->formatPrompt('Data: {data}', ['data' => ['a', 'b']]);

        expect($result)->toBe('Data: ["a","b"]');
    });

    test('handles numeric values', function () {
        $result = $this->manager->formatPrompt('Count: {count}', ['count' => 42]);

        expect($result)->toBe('Count: 42');
    });

    test('leaves unmatched placeholders as-is', function () {
        $result = $this->manager->formatPrompt('Hello {name}, {unknown}!', ['name' => 'Igor']);

        expect($result)->toBe('Hello Igor, {unknown}!');
    });

    test('handles empty context array', function () {
        $result = $this->manager->formatPrompt('No replacements here', []);

        expect($result)->toBe('No replacements here');
    });
});
