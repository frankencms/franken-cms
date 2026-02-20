<?php

namespace FrankenCms\SettingsCasts;

use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedSettingsCast implements SettingsCast
{
    /**
     * Get the value from the payload
     */
    public function get($payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        try {
            return Crypt::decryptString($payload);
        } catch (Exception $e) {
            Log::warning('Failed to decrypt setting value. This may indicate an APP_KEY rotation. Re-enter the value in settings.', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set the value to the payload
     */
    public function set($payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        return Crypt::encryptString($payload);
    }
}
