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
            "Fritz called in sick — I’ve got this! 💪",
            "Gathering power... ⚡️",
            "Charging the batteries... 🔋",
            "Mixing creative potions... 🧪",
            "Consulting ancient texts... 📜",
            "Robbing graves, taking names... 🪦",
            "Buffing my hump... 😏",
            "Installing the brain... checking vision... 👁️",
            "Good brains don’t end up in jars, do they? 🧠",
            "Stitching sentences together... 🪡",
            "Animating the prose... ⚙️",
            "Electrifying the paragraphs... 📖",
            "Raising the prose from its slumber... 🧟‍♂️",
            "Brewing up brilliance... ☕️",
            "Sparking brilliant ideas... 💡",
            "Adding a touch of genius... ✨",
            "Crafting your masterpiece... 🎨",
            "Monster? He’s actually kinda nice. 🤔",
            "Yes, Master. Right away, Master. (So bossy...) 😒",
            "Disposing of... extra parts. 🦴😬",
            "I’ve got a hunch this’ll be good. 😏",
            "Running from the townsfolk... 🏃‍♂️🔥",
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
