<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiModelService
{
    /**
     * Cache TTL in seconds (24 hours)
     */
    protected const CACHE_TTL = 86400;

    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'franken_cms:ai_models:';

    /**
     * Get available models for a provider
     * Uses cache if available, fetches from API otherwise
     */
    public function getModelsForProvider(string $provider): array
    {
        $cacheKey = self::CACHE_PREFIX . $provider;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $models = $this->fetchModelsFromApi($provider);
            if (! empty($models)) {
                Cache::put($cacheKey, $models, self::CACHE_TTL);

                return $models;
            }
        } catch (Exception $e) {
            Log::warning("Failed to fetch models for {$provider}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Force refresh models from API
     */
    public function refreshModels(string $provider): array
    {
        $cacheKey = self::CACHE_PREFIX . $provider;

        $models = $this->fetchModelsFromApi($provider);

        if (! empty($models)) {
            Cache::put($cacheKey, $models, self::CACHE_TTL);

            return $models;
        }

        return [];
    }

    /**
     * Resolve the configured API key for a provider from config/ai.php
     */
    protected function resolveKey(string $provider): ?string
    {
        return config("ai.providers.{$provider}.key");
    }

    /**
     * Curated fallback model list shown when no API key is configured yet,
     * so provider/model selection stays usable before the user finishes setup.
     */
    protected function getCuratedModels(string $provider): array
    {
        return match ($provider) {
            'openai' => [
                'gpt-4o'      => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4.1'     => 'GPT-4.1',
                'o3-mini'     => 'O3 Mini',
            ],
            'anthropic' => [
                'claude-opus-4-5'   => 'Claude Opus 4.5',
                'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
                'claude-haiku-4-5'  => 'Claude Haiku 4.5',
            ],
            'gemini' => [
                'gemini-2.5-pro'   => 'Gemini 2.5 Pro',
                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
            ],
            default => [],
        };
    }

    /**
     * Clear cached models for a provider
     */
    public function clearCache(?string $provider = null): void
    {
        if ($provider) {
            Cache::forget(self::CACHE_PREFIX . $provider);

            return;
        }

        foreach (array_keys(config('ai.providers', [])) as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Check if models are cached for a provider
     */
    public function hasCachedModels(string $provider): bool
    {
        return Cache::has(self::CACHE_PREFIX . $provider);
    }

    /**
     * Get the base URL for a provider from config/ai.php
     */
    protected function getProviderUrl(string $provider): string
    {
        return config("ai.providers.{$provider}.url", '');
    }

    /**
     * Fetch models from provider API.
     * Skips the API call and returns the curated fallback list when no key
     * is configured (Ollama needs no key, so it always fetches live).
     */
    protected function fetchModelsFromApi(string $provider): array
    {
        $apiKey = $this->resolveKey($provider);

        if (empty($apiKey) && $provider !== 'ollama') {
            return $this->getCuratedModels($provider);
        }

        return match ($provider) {
            'openai'    => $this->fetchOpenAiModels($apiKey ?? ''),
            'anthropic' => $this->fetchAnthropicModels($apiKey ?? ''),
            'ollama'    => $this->fetchOllamaModels(),
            'gemini'    => $this->fetchGeminiModels($apiKey ?? ''),
            default     => $this->fetchOpenAiCompatibleModels($provider, $apiKey ?? ''),
        };
    }

    /**
     * Fetch available models from OpenAI API
     */
    protected function fetchOpenAiModels(string $apiKey): array
    {
        $baseUrl = $this->getProviderUrl('openai');
        $url = rtrim($baseUrl, '/') . '/models';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->get($url);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch OpenAI models: ' . $response->body());
        }

        $data = $response->json();
        $models = [];

        // Exclude non-text-generation models
        $excludedPrefixes = [
            'whisper', 'tts', 'dall-e', 'text-embedding', 'text-moderation',
            'davinci', 'babbage', 'curie', 'ada', 'text-davinci', 'text-babbage',
            'text-curie', 'text-ada', 'code-davinci', 'code-cushman',
            'moderation', 'embedding',
        ];

        foreach ($data['data'] ?? [] as $model) {
            $id = $model['id'];

            if (str_contains($id, ':ft-') || str_contains($id, 'ft:')) {
                continue;
            }

            $isExcluded = false;
            foreach ($excludedPrefixes as $prefix) {
                if (str_starts_with($id, $prefix)) {
                    $isExcluded = true;
                    break;
                }
            }

            if ($isExcluded) {
                continue;
            }

            $models[$id] = $this->formatModelId($id);
        }

        arsort($models);

        return $models;
    }

    /**
     * Fetch available models from Anthropic API
     */
    protected function fetchAnthropicModels(string $apiKey): array
    {
        $baseUrl = $this->getProviderUrl('anthropic');
        $models = [];
        $afterId = null;

        do {
            $url = rtrim($baseUrl, '/') . '/models';
            if ($afterId) {
                $url .= '?after_id=' . urlencode($afterId);
            }

            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->get($url);

            if (! $response->successful()) {
                if ($response->status() === 401) {
                    throw new Exception('Invalid Anthropic API key');
                }
                throw new Exception('Failed to fetch Anthropic models: ' . $response->body());
            }

            $data = $response->json();

            foreach ($data['data'] ?? [] as $model) {
                if (($model['type'] ?? '') === 'model') {
                    $id = $model['id'];
                    $models[$id] = $model['display_name'] ?? $this->formatModelId($id);
                }
            }

            $hasMore = $data['has_more'] ?? false;
            $afterId = $data['last_id'] ?? null;

        } while ($hasMore && $afterId);

        arsort($models);

        return $models;
    }

    /**
     * Fetch available models from Ollama local API
     */
    protected function fetchOllamaModels(): array
    {
        $baseUrl = $this->getProviderUrl('ollama');
        $url = rtrim($baseUrl, '/') . '/api/tags';

        $response = Http::get($url);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch Ollama models: ' . $response->body());
        }

        $data = $response->json();
        $models = [];

        foreach ($data['models'] ?? [] as $model) {
            $name = $model['name'];
            $models[$name] = $this->formatModelId($name);
        }

        ksort($models);

        return $models;
    }

    /**
     * Fetch available models from Google Gemini API
     */
    protected function fetchGeminiModels(string $apiKey): array
    {
        $baseUrl = $this->getProviderUrl('gemini');
        $url = $baseUrl . '?key=' . $apiKey;

        $response = Http::get($url);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch Gemini models: ' . $response->body());
        }

        $data = $response->json();
        $models = [];

        foreach ($data['models'] ?? [] as $model) {
            $methods = $model['supportedGenerationMethods'] ?? [];

            if (! in_array('generateContent', $methods)) {
                continue;
            }

            $fullName = $model['name'] ?? '';
            $id = str_starts_with($fullName, 'models/') ? substr($fullName, 7) : $fullName;
            $displayName = $model['displayName'] ?? $this->formatModelId($id);
            $models[$id] = $displayName;
        }

        arsort($models);

        return $models;
    }

    /**
     * Fetch models from any OpenAI-compatible API (OpenRouter, Groq, DeepSeek, xAI, Mistral, etc.)
     */
    protected function fetchOpenAiCompatibleModels(string $provider, string $apiKey): array
    {
        $baseUrl = $this->getProviderUrl($provider);

        if (! $baseUrl) {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/models';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->get($url);

        if (! $response->successful()) {
            throw new Exception("Failed to fetch {$provider} models: " . $response->body());
        }

        $data = $response->json();
        $models = [];

        foreach ($data['data'] ?? [] as $model) {
            $id = $model['id'];
            $models[$id] = $model['name'] ?? $this->formatModelId($id);
        }

        arsort($models);

        return $models;
    }

    /**
     * Generic model ID to human-readable label
     */
    protected function formatModelId(string $modelId): string
    {
        $label = str_replace(['-', '_'], ' ', $modelId);

        // Strip trailing date stamps like "20250514"
        $label = preg_replace('/\s\d{8}$/', '', $label);

        return ucwords($label);
    }
}
