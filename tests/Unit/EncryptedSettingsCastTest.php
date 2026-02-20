<?php

use FrankenCms\SettingsCasts\EncryptedSettingsCast;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->cast = new EncryptedSettingsCast([]);
});

describe('set', function () {
    test('encrypts a string value', function () {
        $encrypted = $this->cast->set('my-secret-key');

        expect($encrypted)->toBeString()->not->toBe('my-secret-key');
    });

    test('returns null for null input', function () {
        $result = $this->cast->set(null);

        expect($result)->toBeNull();
    });

    test('returns null for empty string', function () {
        $result = $this->cast->set('');

        expect($result)->toBeNull();
    });
});

describe('get', function () {
    test('decrypts an encrypted value', function () {
        $encrypted = $this->cast->set('my-secret-key');
        $decrypted = $this->cast->get($encrypted);

        expect($decrypted)->toBe('my-secret-key');
    });

    test('returns null for null input', function () {
        $result = $this->cast->get(null);

        expect($result)->toBeNull();
    });

    test('returns null for empty string', function () {
        $result = $this->cast->get('');

        expect($result)->toBeNull();
    });

    test('returns null for invalid encrypted payload', function () {
        $result = $this->cast->get('not-a-valid-encrypted-string');

        expect($result)->toBeNull();
    });

    test('logs a warning when decryption fails', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Failed to decrypt'));

        $cast = new EncryptedSettingsCast([]);

        $result = $cast->get('invalid-encrypted-payload');

        expect($result)->toBeNull();
    });
});

describe('round-trip', function () {
    test('encrypts and decrypts preserving the original value', function () {
        $original = 'sk-abc123def456';
        $encrypted = $this->cast->set($original);
        $decrypted = $this->cast->get($encrypted);

        expect($decrypted)->toBe($original);
    });

    test('produces different ciphertext for same plaintext', function () {
        $encrypted1 = $this->cast->set('same-value');
        $encrypted2 = $this->cast->set('same-value');

        // Laravel encryption includes randomness, so ciphertexts differ
        expect($encrypted1)->not->toBe($encrypted2);
    });
});
