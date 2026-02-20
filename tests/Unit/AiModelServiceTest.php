<?php

use FrankenCms\Services\AiModelService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new AiModelService;
    Cache::flush();

    // Set up Prism provider URLs used by the service
    config()->set('prism.providers.openai.url', 'https://api.openai.com/v1');
    config()->set('prism.providers.anthropic.url', 'https://api.anthropic.com/v1');
    config()->set('prism.providers.ollama.url', 'http://localhost:11434');
    config()->set('prism.providers.gemini.url', 'https://generativelanguage.googleapis.com/v1beta/models');
    config()->set('prism.providers.openrouter.url', 'https://openrouter.ai/api/v1');
    config()->set('prism.providers.groq.url', 'https://api.groq.com/openai/v1');
});

describe('getModelsForProvider', function () {
    test('returns cached models when available', function () {
        $cachedModels = ['gpt-4o' => 'GPT 4o', 'gpt-4o-mini' => 'GPT 4o Mini'];
        Cache::put('franken_cms:ai_models:openai', $cachedModels, 86400);

        $result = $this->service->getModelsForProvider('openai', 'sk-test');

        expect($result)->toBe($cachedModels);
    });

    test('fetches from API when no cache and API key provided', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o'],
                    ['id' => 'gpt-4o-mini'],
                ],
            ]),
        ]);

        $result = $this->service->getModelsForProvider('openai', 'sk-test');

        expect($result)->not->toBeEmpty();
        expect($result)->toHaveKey('gpt-4o');
    });

    test('returns empty array when no cache and no API key', function () {
        $result = $this->service->getModelsForProvider('openai');

        expect($result)->toBeEmpty();
    });

    test('returns empty array when API fails', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response('Server error', 500),
        ]);

        $result = $this->service->getModelsForProvider('openai', 'sk-test');

        expect($result)->toBeEmpty();
    });

    test('fetches Ollama models without API key', function () {
        Http::fake([
            'localhost:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'llama3'],
                    ['name' => 'mistral'],
                ],
            ]),
        ]);

        $result = $this->service->getModelsForProvider('ollama');

        expect($result)->not->toBeEmpty();
        expect($result)->toHaveKey('llama3');
    });
});

describe('refreshModels', function () {
    test('fetches fresh models from OpenAI API', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o'],
                    ['id' => 'o3-mini'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('openai', 'sk-test');

        expect($result)->toHaveKey('gpt-4o');
        expect($result)->toHaveKey('o3-mini');
    });

    test('caches refreshed models', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o'],
                ],
            ]),
        ]);

        $this->service->refreshModels('openai', 'sk-test');

        expect(Cache::has('franken_cms:ai_models:openai'))->toBeTrue();
    });

    test('throws exception on API failure', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response('Unauthorized', 401),
        ]);

        $this->service->refreshModels('openai', 'bad-key');
    })->throws(Exception::class);

    test('returns empty array when API returns empty data', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response(['data' => []]),
        ]);

        $result = $this->service->refreshModels('openai', 'sk-test');

        expect($result)->toBeEmpty();
    });
});

describe('fetchOpenAiModels filtering', function () {
    test('excludes non-text-generation models', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o'],
                    ['id' => 'whisper-1'],
                    ['id' => 'tts-1'],
                    ['id' => 'dall-e-3'],
                    ['id' => 'text-embedding-3-large'],
                    ['id' => 'o3-mini'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('openai', 'sk-test');

        expect($result)->toHaveKey('gpt-4o');
        expect($result)->toHaveKey('o3-mini');
        expect($result)->not->toHaveKey('whisper-1');
        expect($result)->not->toHaveKey('tts-1');
        expect($result)->not->toHaveKey('dall-e-3');
        expect($result)->not->toHaveKey('text-embedding-3-large');
    });

    test('excludes fine-tuned models', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o'],
                    ['id' => 'ft:gpt-4o:my-org:custom:abc123'],
                    ['id' => 'gpt-3.5-turbo:ft-custom-2024'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('openai', 'sk-test');

        expect($result)->toHaveKey('gpt-4o');
        expect(array_keys($result))->toHaveCount(1);
    });
});

describe('fetchAnthropicModels', function () {
    test('fetches models from Anthropic API', function () {
        Http::fake([
            'api.anthropic.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'claude-sonnet-4-20250514', 'type' => 'model', 'display_name' => 'Claude Sonnet 4'],
                    ['id' => 'claude-haiku-4-20250414', 'type' => 'model', 'display_name' => 'Claude Haiku 4'],
                ],
                'has_more' => false,
            ]),
        ]);

        $result = $this->service->refreshModels('anthropic', 'sk-ant-test');

        expect($result)->toHaveKey('claude-sonnet-4-20250514');
        expect($result['claude-sonnet-4-20250514'])->toBe('Claude Sonnet 4');
    });

    test('paginates through all models', function () {
        Http::fake([
            'api.anthropic.com/v1/models?after_id=*' => Http::response([
                'data' => [
                    ['id' => 'claude-haiku-4-20250414', 'type' => 'model', 'display_name' => 'Claude Haiku 4'],
                ],
                'has_more' => false,
            ]),
            'api.anthropic.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'claude-sonnet-4-20250514', 'type' => 'model', 'display_name' => 'Claude Sonnet 4'],
                ],
                'has_more' => true,
                'last_id'  => 'claude-sonnet-4-20250514',
            ]),
        ]);

        $result = $this->service->refreshModels('anthropic', 'sk-ant-test');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('claude-sonnet-4-20250514');
        expect($result)->toHaveKey('claude-haiku-4-20250414');
    });

    test('throws exception for invalid API key', function () {
        Http::fake([
            'api.anthropic.com/v1/models' => Http::response('Unauthorized', 401),
        ]);

        $this->service->refreshModels('anthropic', 'bad-key');
    })->throws(Exception::class, 'Invalid Anthropic API key');

    test('throws exception for non-401 API errors', function () {
        Http::fake([
            'api.anthropic.com/v1/models' => Http::response('Internal Server Error', 500),
        ]);

        $this->service->refreshModels('anthropic', 'sk-ant-test');
    })->throws(Exception::class, 'Failed to fetch Anthropic models');

    test('skips non-model types', function () {
        Http::fake([
            'api.anthropic.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'claude-sonnet-4-20250514', 'type' => 'model', 'display_name' => 'Claude Sonnet 4'],
                    ['id' => 'some-finetune', 'type' => 'fine-tune', 'display_name' => 'Custom Model'],
                ],
                'has_more' => false,
            ]),
        ]);

        $result = $this->service->refreshModels('anthropic', 'sk-ant-test');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('claude-sonnet-4-20250514');
    });

    test('uses display_name from API response', function () {
        Http::fake([
            'api.anthropic.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'claude-sonnet-4-20250514', 'type' => 'model', 'display_name' => 'Claude Sonnet 4'],
                ],
                'has_more' => false,
            ]),
        ]);

        $result = $this->service->refreshModels('anthropic', 'sk-ant-test');

        expect($result['claude-sonnet-4-20250514'])->toBe('Claude Sonnet 4');
    });
});

describe('fetchOllamaModels', function () {
    test('fetches models from Ollama local API', function () {
        Http::fake([
            'localhost:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'llama3'],
                    ['name' => 'mistral'],
                    ['name' => 'codellama'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('ollama', '');

        expect($result)->toHaveCount(3);
        expect($result)->toHaveKey('llama3');
        expect($result)->toHaveKey('mistral');
        expect($result)->toHaveKey('codellama');
    });

    test('throws exception when Ollama is not running', function () {
        Http::fake([
            'localhost:11434/api/tags' => Http::response('Connection refused', 500),
        ]);

        $this->service->refreshModels('ollama', '');
    })->throws(Exception::class, 'Failed to fetch Ollama models');

    test('returns empty array when no models installed', function () {
        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => []]),
        ]);

        $result = $this->service->refreshModels('ollama', '');

        expect($result)->toBeEmpty();
    });
});

describe('fetchGeminiModels', function () {
    test('fetches models from Gemini API', function () {
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models*' => Http::response([
                'models' => [
                    [
                        'name'                       => 'models/gemini-2.0-flash',
                        'displayName'                => 'Gemini 2.0 Flash',
                        'supportedGenerationMethods' => ['generateContent'],
                    ],
                    [
                        'name'                       => 'models/gemini-1.5-pro',
                        'displayName'                => 'Gemini 1.5 Pro',
                        'supportedGenerationMethods' => ['generateContent'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('gemini', 'test-key');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('gemini-2.0-flash');
        expect($result['gemini-2.0-flash'])->toBe('Gemini 2.0 Flash');
    });

    test('strips models/ prefix from Gemini model names', function () {
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models*' => Http::response([
                'models' => [
                    [
                        'name'                       => 'models/gemini-2.0-flash',
                        'displayName'                => 'Gemini 2.0 Flash',
                        'supportedGenerationMethods' => ['generateContent'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('gemini', 'test-key');

        expect($result)->toHaveKey('gemini-2.0-flash');
        expect($result)->not->toHaveKey('models/gemini-2.0-flash');
    });

    test('filters out non-generateContent models', function () {
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models*' => Http::response([
                'models' => [
                    [
                        'name'                       => 'models/gemini-2.0-flash',
                        'displayName'                => 'Gemini 2.0 Flash',
                        'supportedGenerationMethods' => ['generateContent'],
                    ],
                    [
                        'name'                       => 'models/text-embedding-004',
                        'displayName'                => 'Text Embedding 004',
                        'supportedGenerationMethods' => ['embedContent'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('gemini', 'test-key');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('gemini-2.0-flash');
        expect($result)->not->toHaveKey('text-embedding-004');
    });

    test('throws exception on API failure', function () {
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models*' => Http::response('Forbidden', 403),
        ]);

        $this->service->refreshModels('gemini', 'bad-key');
    })->throws(Exception::class, 'Failed to fetch Gemini models');
});

describe('fetchOpenAiCompatibleModels', function () {
    test('fetches models from OpenAI-compatible API', function () {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    ['id' => 'openai/gpt-4o', 'name' => 'GPT-4o'],
                    ['id' => 'anthropic/claude-sonnet-4', 'name' => 'Claude Sonnet 4'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('openrouter', 'test-key');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('openai/gpt-4o');
        expect($result['openai/gpt-4o'])->toBe('GPT-4o');
    });

    test('works for Groq provider', function () {
        Http::fake([
            'api.groq.com/openai/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama-3.3-70b-versatile'],
                    ['id' => 'mixtral-8x7b-32768'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('groq', 'test-key');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('llama-3.3-70b-versatile');
    });

    test('returns empty array when provider has no URL configured', function () {
        config()->set('prism.providers.unknown', []);

        $result = $this->service->refreshModels('unknown', 'test-key');

        expect($result)->toBeEmpty();
    });

    test('throws exception on API failure', function () {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response('Unauthorized', 401),
        ]);

        $this->service->refreshModels('openrouter', 'bad-key');
    })->throws(Exception::class, 'Failed to fetch openrouter models');
});

describe('formatModelId', function () {
    test('formats model IDs to human-readable labels', function () {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4o-mini'],
                ],
            ]),
        ]);

        $result = $this->service->refreshModels('openai', 'sk-test');

        expect($result['gpt-4o-mini'])->toBe('Gpt 4o Mini');
    });

    test('strips trailing date stamps from Anthropic model IDs', function () {
        Http::fake([
            'api.anthropic.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'claude-sonnet-4-20250514', 'type' => 'model'],
                ],
                'has_more' => false,
            ]),
        ]);

        $result = $this->service->refreshModels('anthropic', 'sk-ant-test');

        // Without display_name, formatModelId should strip the date
        expect($result['claude-sonnet-4-20250514'])->toBe('Claude Sonnet 4');
    });
});

describe('clearCache', function () {
    test('clears cache for specific provider', function () {
        Cache::put('franken_cms:ai_models:openai', ['model' => 'label'], 86400);

        $this->service->clearCache('openai');

        expect(Cache::has('franken_cms:ai_models:openai'))->toBeFalse();
    });

    test('clears all provider caches when no provider specified', function () {
        Cache::put('franken_cms:ai_models:openai', ['model' => 'label'], 86400);
        Cache::put('franken_cms:ai_models:anthropic', ['model' => 'label'], 86400);
        Cache::put('franken_cms:ai_models:ollama', ['model' => 'label'], 86400);

        $this->service->clearCache();

        expect(Cache::has('franken_cms:ai_models:openai'))->toBeFalse();
        expect(Cache::has('franken_cms:ai_models:anthropic'))->toBeFalse();
        expect(Cache::has('franken_cms:ai_models:ollama'))->toBeFalse();
    });
});

describe('hasCachedModels', function () {
    test('returns false when no cache exists', function () {
        expect($this->service->hasCachedModels('openai'))->toBeFalse();
    });

    test('returns true when cache exists', function () {
        Cache::put('franken_cms:ai_models:openai', ['model' => 'label'], 86400);

        expect($this->service->hasCachedModels('openai'))->toBeTrue();
    });
});
