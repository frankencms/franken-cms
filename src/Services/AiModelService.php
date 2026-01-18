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
     * Known Anthropic models (curated list since they don't have a public models API)
     * This list is used as fallback when API fetch isn't available
     */
    protected const ANTHROPIC_MODELS = [
        'claude-sonnet-4-20250514'   => 'Claude Sonnet 4 (Latest)',
        'claude-3-7-sonnet-20250219' => 'Claude 3.7 Sonnet',
        'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
        'claude-3-5-haiku-20241022'  => 'Claude 3.5 Haiku (Fast)',
        'claude-3-opus-20240229'     => 'Claude 3 Opus',
        'claude-3-sonnet-20240229'   => 'Claude 3 Sonnet',
        'claude-3-haiku-20240307'    => 'Claude 3 Haiku',
    ];

    /**
     * Get available models for a provider
     * Uses cache if available, falls back to config
     */
    public function getModelsForProvider(string $provider, ?string $apiKey = null): array
    {
        // Check cache first
        $cacheKey = self::CACHE_PREFIX . $provider;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // Try to fetch from API if we have a key
        if ($apiKey) {
            try {
                $models = $this->fetchModelsFromApi($provider, $apiKey);
                if (! empty($models)) {
                    Cache::put($cacheKey, $models, self::CACHE_TTL);

                    return $models;
                }
            } catch (Exception $e) {
                Log::warning("Failed to fetch models for {$provider}: " . $e->getMessage());
            }
        }

        // Fall back to config or curated list
        return $this->getFallbackModels($provider);
    }

    /**
     * Force refresh models from API
     */
    public function refreshModels(string $provider, string $apiKey): array
    {
        $cacheKey = self::CACHE_PREFIX . $provider;

        try {
            $models = $this->fetchModelsFromApi($provider, $apiKey);
            if (! empty($models)) {
                Cache::put($cacheKey, $models, self::CACHE_TTL);

                return $models;
            }
        } catch (Exception $e) {
            Log::error("Failed to refresh models for {$provider}: " . $e->getMessage());
            throw $e;
        }

        return $this->getFallbackModels($provider);
    }

    /**
     * Clear cached models for a provider
     */
    public function clearCache(?string $provider = null): void
    {
        if ($provider) {
            Cache::forget(self::CACHE_PREFIX . $provider);
        } else {
            // Clear all known provider caches
            Cache::forget(self::CACHE_PREFIX . 'openai');
            Cache::forget(self::CACHE_PREFIX . 'anthropic');
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
     * Fetch models from provider API
     */
    protected function fetchModelsFromApi(string $provider, string $apiKey): array
    {
        return match ($provider) {
            'openai'    => $this->fetchOpenAiModels($apiKey),
            'anthropic' => $this->fetchAnthropicModels($apiKey),
            default     => [],
        };
    }

    /**
     * Fetch available models from OpenAI API
     */
    protected function fetchOpenAiModels(string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->get('https://api.openai.com/v1/models');

        if (! $response->successful()) {
            throw new Exception('Failed to fetch OpenAI models: ' . $response->body());
        }

        $data = $response->json();
        $models = [];

        // Filter and sort models - focus on GPT models suitable for text generation
        $relevantPrefixes = ['gpt-4', 'gpt-3.5', 'o1', 'o3'];

        foreach ($data['data'] ?? [] as $model) {
            $id = $model['id'];

            // Skip non-GPT models and fine-tuned models
            $isRelevant = false;
            foreach ($relevantPrefixes as $prefix) {
                if (str_starts_with($id, $prefix)) {
                    $isRelevant = true;
                    break;
                }
            }

            if (! $isRelevant || str_contains($id, ':ft-')) {
                continue;
            }

            // Create human-readable label
            $label = $this->formatOpenAiModelLabel($id);
            $models[$id] = $label;
        }

        // Sort by model name (newer models typically have higher version numbers)
        uksort($models, fn ($a, $b) => $this->compareOpenAiModels($b, $a));

        return $models;
    }

    /**
     * Fetch available models from Anthropic API
     */
    protected function fetchAnthropicModels(string $apiKey): array
    {
        $models = [];
        $afterId = null;

        // Paginate through all models
        do {
            $url = 'https://api.anthropic.com/v1/models';
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
                // Only include models of type "model" (not fine-tunes, etc.)
                if (($model['type'] ?? '') === 'model') {
                    $id = $model['id'];
                    $label = $model['display_name'] ?? $this->formatAnthropicModelLabel($id);
                    $models[$id] = $label;
                }
            }

            $hasMore = $data['has_more'] ?? false;
            $afterId = $data['last_id'] ?? null;

        } while ($hasMore && $afterId);

        // Sort models by release date (newer first) - model IDs contain dates
        uksort($models, fn ($a, $b) => $this->compareAnthropicModels($b, $a));

        return $models;
    }

    /**
     * Format Anthropic model ID to human-readable label (fallback if display_name not provided)
     */
    protected function formatAnthropicModelLabel(string $modelId): string
    {
        // Extract version and date from model ID like "claude-3-5-sonnet-20241022"
        if (preg_match('/^claude-(\d+(?:-\d+)?)-(\w+)-(\d{8})$/', $modelId, $matches)) {
            $version = str_replace('-', '.', $matches[1]);
            $variant = ucfirst($matches[2]);

            return "Claude {$version} {$variant}";
        }

        // Handle newer format like "claude-sonnet-4-20250514"
        if (preg_match('/^claude-(\w+)-(\d+)-(\d{8})$/', $modelId, $matches)) {
            $variant = ucfirst($matches[1]);
            $version = $matches[2];

            return "Claude {$variant} {$version}";
        }

        // Fallback: basic formatting
        return ucwords(str_replace('-', ' ', $modelId));
    }

    /**
     * Compare Anthropic model names for sorting (newer models first)
     */
    protected function compareAnthropicModels(string $a, string $b): int
    {
        // Extract dates from model IDs for sorting
        $dateA = $this->extractAnthropicModelDate($a);
        $dateB = $this->extractAnthropicModelDate($b);

        if ($dateA !== $dateB) {
            return $dateA <=> $dateB;
        }

        // Same date, sort by version/variant
        return strcmp($a, $b);
    }

    /**
     * Extract date from Anthropic model ID
     */
    protected function extractAnthropicModelDate(string $modelId): string
    {
        if (preg_match('/(\d{8})$/', $modelId, $matches)) {
            return $matches[1];
        }

        return '00000000';
    }

    /**
     * Get fallback models from config or curated list
     */
    protected function getFallbackModels(string $provider): array
    {
        // Try config first
        $configModels = config("franken-cms.ai_providers.{$provider}.models", []);

        if (! empty($configModels)) {
            return $configModels;
        }

        // Use curated list for Anthropic
        if ($provider === 'anthropic') {
            return self::ANTHROPIC_MODELS;
        }

        // Default fallback for OpenAI
        if ($provider === 'openai') {
            return [
                'gpt-4o'        => 'GPT-4o (Recommended)',
                'gpt-4o-mini'   => 'GPT-4o Mini (Faster)',
                'gpt-4-turbo'   => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ];
        }

        return [];
    }

    /**
     * Format OpenAI model ID to human-readable label
     */
    protected function formatOpenAiModelLabel(string $modelId): string
    {
        // Handle special cases
        $labelMap = [
            'gpt-4o'              => 'GPT-4o (Omni)',
            'gpt-4o-mini'         => 'GPT-4o Mini (Fast & Affordable)',
            'gpt-4-turbo'         => 'GPT-4 Turbo',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo Preview',
            'gpt-4'               => 'GPT-4',
            'gpt-3.5-turbo'       => 'GPT-3.5 Turbo',
            'o1'                  => 'o1 (Reasoning)',
            'o1-mini'             => 'o1 Mini (Reasoning)',
            'o1-preview'          => 'o1 Preview (Reasoning)',
            'o3-mini'             => 'o3 Mini (Reasoning)',
        ];

        if (isset($labelMap[$modelId])) {
            return $labelMap[$modelId];
        }

        // Format other models
        $label = str_replace(['gpt-', '-'], ['GPT-', ' '], $modelId);

        return ucwords($label);
    }

    /**
     * Compare OpenAI model names for sorting (newer/better models first)
     */
    protected function compareOpenAiModels(string $a, string $b): int
    {
        // Priority order: o3 > o1 > gpt-4o > gpt-4 > gpt-3.5
        $priority = [
            'o3'      => 5,
            'o1'      => 4,
            'gpt-4o'  => 3,
            'gpt-4'   => 2,
            'gpt-3.5' => 1,
        ];

        $aPriority = 0;
        $bPriority = 0;

        foreach ($priority as $prefix => $p) {
            if (str_starts_with($a, $prefix)) {
                $aPriority = $p;
            }
            if (str_starts_with($b, $prefix)) {
                $bPriority = $p;
            }
        }

        if ($aPriority !== $bPriority) {
            return $aPriority <=> $bPriority;
        }

        // Same priority, sort alphabetically (which tends to put newer versions first)
        return strcmp($a, $b);
    }
}
