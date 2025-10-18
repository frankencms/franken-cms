<?php

namespace FrankenCms\SettingsCasts;

use Exception;
use Illuminate\Support\Facades\Crypt;
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
            // If decryption fails, return null
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
