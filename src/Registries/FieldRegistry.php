<?php

namespace FrankenCms\Registries;

class FieldRegistry
{
    protected static array $fields = [];

    public static function register($identifier, $type, $properties = []): void
    {
        static::$fields[$identifier] = [
            'type'       => $type,
            'properties' => $properties,
        ];
    }

    public static function getFields(): array
    {
        return static::$fields;
    }

    public static function reset(): void
    {
        static::$fields = [];
    }
}
