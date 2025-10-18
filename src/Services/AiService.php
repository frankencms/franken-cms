<?php

namespace FrankenCms\Services;


use Exception;
use FrankenCms\Prompts\PromptManager;
use FrankenCms\Settings\AiSettings;
use Prism\Prism\Prism;

class AiService
{
    public function __construct(
        protected PromptManager $promptManager
    ) {}

    /**
     * Generate content using AI
     *
     *
     * @throws Exception
     */
    public function generate(string $actionKey, array $context): string
    {
        if (! AiFeatureDetector::isAvailable()) {
            throw new Exception('AI features are not available. Please install prism-php/prism and configure Igor in settings.');
        }

        // Get prompt configuration
        $promptConfig = $this->promptManager->getPrompt($actionKey);

        // Format prompt with context variables
        $formattedPrompt = $this->promptManager->formatPrompt(
            $promptConfig['prompt'],
            $context
        );

        // Get AI settings
        $settings = app(AiSettings::class);

        try {
            // Call Prism to generate content
            $prismRequest = Prism::text()
                ->using($settings->provider, $settings->model)
                ->withPrompt($formattedPrompt)
                ->withMaxTokens($promptConfig['max_tokens'] ?? 500);

            // Only set temperature if configured in the prompt
            if (isset($promptConfig['temperature'])) {
                $prismRequest->usingTemperature($promptConfig['temperature']);
            }

            $response = $prismRequest->generate();

            return trim($response->text);
        } catch (Exception $e) {
            throw new Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Test provider connection
     */
    public function testConnection(): bool
    {
        if (! AiFeatureDetector::isPrismInstalled()) {
            return false;
        }

        try {
            $settings = app(AiSettings::class);

            if (empty($settings->api_key) && $settings->provider !== 'ollama') {
                return false;
            }

            $response = Prism::text()
                ->using($settings->provider, $settings->model)
                ->withPrompt('Respond with only the word "OK"')
                ->withMaxTokens(10)
                ->generate();

            return ! empty($response->text);
        } catch (Exception $e) {
            return false;
        }
    }
}
