<?php

namespace FrankenCms\Support;

class FocalPoint
{
    /**
     * Normalize any stored focal point representation into x/y percentages.
     * The pickers store the string form ('94% 22%'); older data may hold
     * ['x' => 94, 'y' => 22]. Anything unrecognizable resolves to center.
     *
     * @return array{x: int, y: int}
     */
    public static function normalize(mixed $value): array
    {
        if (is_array($value)) {
            return [
                'x' => self::clamp($value['x'] ?? 50),
                'y' => self::clamp($value['y'] ?? 50),
            ];
        }

        if (is_string($value) && preg_match('/^(-?[\d.]+)%\s+(-?[\d.]+)%$/', trim($value), $matches)) {
            return [
                'x' => self::clamp($matches[1]),
                'y' => self::clamp($matches[2]),
            ];
        }

        return ['x' => 50, 'y' => 50];
    }

    /**
     * The picker/state string form: '94% 22%'
     *
     * @param  array{x: int, y: int}  $point
     */
    public static function toPercentString(array $point): string
    {
        return "{$point['x']}% {$point['y']}%";
    }

    /**
     * A ready-to-inline CSS declaration for any stored representation
     */
    public static function toCss(mixed $value): string
    {
        $point = self::normalize($value);

        return "object-position: {$point['x']}% {$point['y']}%;";
    }

    protected static function clamp(mixed $value): int
    {
        return (int) max(0, min(100, round((float) $value)));
    }
}
