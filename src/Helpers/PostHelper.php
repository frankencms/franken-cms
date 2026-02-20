<?php

namespace FrankenCms\Helpers;

use Exception;
use FrankenCms\Settings\ReadingSettings;

final class PostHelper
{
    public static function calculate_read_time(string $htmlContent, int $averageReadingSpeed = 225): int
    {

        // Remove script and style content
        $htmlContent = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/\1>/is', '', $htmlContent);

        // Remove HTML tags to isolate the text
        $textContent = strip_tags((string) $htmlContent);

        // Decode HTML entities
        $textContent = html_entity_decode($textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Count the number of words in the text using Unicode regex
        $wordCount = preg_match_all('/\p{L}+/u', $textContent, $matches);

        // Avoid division by zero
        if ($averageReadingSpeed <= 0) {
            $averageReadingSpeed = 225;
        }

        // Calculate the estimated read time in minutes
        return (int) ceil($wordCount / $averageReadingSpeed);

    }

    public static function convert_tip_tap_to_plain_text($json): string
    {
        $text = '';

        if (! is_array($json)) {
            return '';
        }

        if (isset($json['content'])) {
            foreach ($json['content'] as $node) {
                if ($node['type'] === 'text' && isset($node['text'])) {
                    $text .= $node['text'] . ' ';
                } elseif (isset($node['content'])) {
                    $text .= self::convert_tip_tap_to_plain_text($node) . ' ';
                }
            }
        }

        return trim($text);
    }

    public static function index_page(): ?string
    {
        return app(ReadingSettings::class)->post_page;
    }

    public static function get_image_dimensions($file): ?array
    {
        try {
            if (is_string($file)) {
                $path = $file;
            } elseif (method_exists($file, 'getRealPath')) {
                $path = $file->getRealPath();
            } else {
                return null;
            }

            $imageInfo = getimagesize($path);
            if ($imageInfo !== false) {
                return [
                    'width'  => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (Exception $e) {
            // Silent fail - dimensions are optional
        }

        return null;
    }
}
