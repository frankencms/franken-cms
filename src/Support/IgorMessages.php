<?php

namespace FrankenCms\Support;

class IgorMessages
{
    /**
     * Get all loading messages
     */
    public static function loadingMessages(): array
    {
        return [
            'Gathering power...',
            'Mixing creative potions...',
            'Consulting ancient texts...',
            'Sparking brilliant ideas...',
            'Crafting your masterpiece...',
            'Adding a touch of genius...',
            'Buffing my hump...',
            'Robbing graves, taken names...',
            'Charging the batteries...',
            'Brewing up brilliance...',
            'Animating the prose...',
            'Conjuring words from the void...',
            'Stitching sentences together...',
            'Electrifying the paragraphs...',
            'Raising the prose from its slumber...',
        ];
    }

    /**
     * Get a random loading message
     */
    public static function randomLoadingMessage(): string
    {
        $messages = self::loadingMessages();

        return $messages[array_rand($messages)];
    }
}
