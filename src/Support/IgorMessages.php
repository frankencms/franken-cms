<?php

declare(strict_types=1);

namespace FrankenCms\Support;

class IgorMessages
{
    /**
     * Get all loading messages
     */
    public static function loadingMessages(): array
    {
        return [
            "Fritz called in sick — I've got this! 💪",
            'Gathering power... ⚡️',
            'Charging the batteries... 🔋',
            'Mixing creative potions... 🧪',
            'Consulting ancient texts... 📜',
            'Robbing graves, taking names... 🪦',
            'Buffing my hump... 😏',
            'Installing the brain... checking vision... 👁️',
            "Good brains don't end up in jars, do they? 🧠",
            'Stitching sentences together... 🪡',
            'Animating the prose... ⚙️',
            'Electrifying the paragraphs... 📖',
            'Raising the prose from its slumber... 🧟‍♂️',
            'Brewing up brilliance... ☕️',
            'Sparking brilliant ideas... 💡',
            'Adding a touch of genius... ✨',
            'Crafting your masterpiece... 🎨',
            "Monster? He's actually kinda nice. 🤔",
            'Yes, Master. Right away, Master. (So bossy...) 😒',
            'Disposing of... extra parts. 🦴😬',
            "I've got a hunch this'll be good. 😏",
            'Running from the townsfolk... 🏃‍♂️🔥',
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

    /**
     * Get installation step messages with both Igor and Dr. Frankenstein
     */
    public static function installationMessages(): array
    {
        return [
            'welcome' => [
                'igor'   => 'Yes, Master! Igor is ready to assist with the installation! 🔨',
                'doctor' => 'Excellent! Tonight... we shall create something MAGNIFICENT! ⚡',
            ],
            'asking_config' => [
                'igor'   => 'Master, shall Igor fetch the configuration scrolls? 📜',
                'doctor' => 'The blueprints! Yes, we may need to customize them later...',
            ],
            'publishing_config' => [
                'igor'   => 'Fetching the configuration scrolls from the vault... 📜',
                'doctor' => 'Excellent! We can customize these as needed!',
            ],
            'skip_config' => [
                'igor'   => 'As you wish, Master. The default scrolls will suffice...',
                'doctor' => 'Very well, we shall use the package defaults!',
            ],
            'asking_migrations' => [
                'igor'   => 'Shall Igor bring the parts from the laboratory storage? 🔧',
                'doctor' => 'We need the building blocks for our creation!',
            ],
            'publishing_migrations' => [
                'igor'   => 'Carrying the heavy parts from storage, Master... 📦',
                'doctor' => 'The foundation of our creation!',
            ],
            'skip_migrations' => [
                'igor'   => 'Igor will leave them in storage for now, Master...',
                'doctor' => 'Perhaps they are already in place... interesting.',
            ],
            'running_migrations' => [
                'igor'   => 'Assembling the parts, Master! This may take a moment... 🔧',
                'doctor' => 'Patience! Greatness cannot be rushed!',
            ],
            'detecting_panels' => [
                'igor'   => 'Searching the castle for suitable laboratories... 🔍',
                'doctor' => 'We must choose the PERFECT environment!',
            ],
            'already_installed' => [
                'igor'   => 'Master! The creature already lives in this panel! 👀',
                'doctor' => 'Curious... it seems our work here is already complete.',
            ],
            'registering_plugin' => [
                'igor'   => 'Connecting the life force to the panel... ⚡',
                'doctor' => 'YES! The moment of truth approaches!',
            ],
            'theme_install' => [
                'igor'   => 'Shall Igor prepare the example templates, Master? 📝',
                'doctor' => 'A fine idea! Show them what we can create!',
            ],
            'success' => [
                'igor'   => "It's ALIVE, Master! The creation breathes! 🧟",
                'doctor' => 'SUCCESS! My masterpiece is COMPLETE! MUHAHAHA! 🎉',
            ],
            'error' => [
                'igor'   => 'Master! Something went wrong in the laboratory! 😱',
                'doctor' => 'BLAST! We must investigate this failure!',
            ],
            'migration_success' => [
                'igor'   => 'All parts assembled perfectly, Master! ✨',
                'doctor' => 'Splendid! The framework is ready!',
            ],
            'migration_error' => [
                'igor'   => 'Master! Some parts are already in place... or perhaps damaged... 😰',
                'doctor' => 'No matter! The existing framework may suffice. We shall proceed!',
            ],
            'backup_created' => [
                'igor'   => 'Igor has made a backup... just in case, Master... 📋',
                'doctor' => 'Smart thinking! One must always prepare for... complications.',
            ],
        ];
    }

    /**
     * Get a specific installation message
     */
    public static function installMessage(string $step, string $character = 'igor'): string
    {
        $messages = static::installationMessages();

        return $messages[$step][$character] ?? '';
    }

    /**
     * Get ASCII art for different situations
     */
    public static function asciiArt(string $type = 'welcome'): string
    {
        return match ($type) {
            'welcome' => <<<'ASCII'
╔══════════════════════════════════════════════════════════════════╗
║                                                                  ║
║   ███████╗██████╗  █████╗ ███╗   ██╗██╗  ██╗███████╗███╗   ██╗   ║
║   ██╔════╝██╔══██╗██╔══██╗████╗  ██║██║ ██╔╝██╔════╝████╗  ██║   ║
║   █████╗  ██████╔╝███████║██╔██╗ ██║█████╔╝ █████╗  ██╔██╗ ██║   ║
║   ██╔══╝  ██╔══██╗██╔══██║██║╚██╗██║██╔═██╗ ██╔══╝  ██║╚██╗██║   ║
║   ██║     ██║  ██║██║  ██║██║ ╚████║██║  ██╗███████╗██║ ╚████║   ║
║   ╚═╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝   ║
║                                                                  ║
║        ██████╗███╗   ███╗███████╗                                ║
║       ██╔════╝████╗ ████║██╔════╝                                ║
║       ██║     ██╔████╔██║███████╗                                ║
║       ██║     ██║╚██╔╝██║╚════██║                                ║
║       ╚██████╗██║ ╚═╝ ██║███████║                                ║
║        ╚═════╝╚═╝     ╚═╝╚══════╝                                ║
║                                                                  ║
╚══════════════════════════════════════════════════════════════════╝
ASCII,
            'success' => <<<'ASCII'

    ⚡⚡⚡ IT'S ALIVE! IT'S ALIVE! ⚡⚡⚡

         _____________________
        |  ___________________  |
        | |                   | |
        | |   IT'S ALIVE!!!   | |
        | |   ⚡ ⚡ ⚡ ⚡ ⚡   | |
        | |___________________| |
        |_____________________|
               \\___\\___\\
               /   /   /
              /___/___/
             |    |    |
             |    |    |
            /|    |    |\
           (_|    |    |_)
              \   |   /
               \  |  /
                \ | /
                 \|/
                  V

    🧟 FrankenCMS Installation Complete! 🧟

ASCII,
            'lightning' => <<<'ASCII'
            ⚡
           ⚡⚡
          ⚡⚡⚡
         ⚡⚡⚡⚡
            ⚡⚡
             ⚡
            ⚡⚡
           ⚡⚡⚡
ASCII,
            'igor' => <<<'ASCII'
     ___
    (o o)
    (   )  < Yes, Master!
    --"--
ASCII,
            'doctor' => <<<'ASCII'
     /\_/\
    ( ^.^ )
     > ^ <  < MAGNIFICENT!
ASCII,
            default => '',
        };
    }
}
