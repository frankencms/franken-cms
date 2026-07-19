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
                'igor'   => 'Yes, Master! Igor is ready to assist with the installation!',
                'doctor' => 'Excellent! Tonight... we shall create something MAGNIFICENT!',
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
                'igor'   => 'Shall Igor bring the parts from the laboratory storage?',
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
                'igor'   => 'Assembling the parts, Master! This may take a moment...',
                'doctor' => 'Patience! Greatness cannot be rushed!',
            ],
            'detecting_panels' => [
                'igor'   => 'Searching the castle for suitable laboratories...',
                'doctor' => 'We must choose the PERFECT environment!',
            ],
            'already_installed' => [
                'igor'   => 'Master! The creature already lives in this panel!',
                'doctor' => 'Curious... it seems our work here is already complete.',
            ],
            'registering_plugin' => [
                'igor'   => 'Connecting the life force to the panel...',
                'doctor' => 'YES! The moment of truth approaches!',
            ],
            'theme_install' => [
                'igor'   => 'Shall Igor prepare the example templates, Master?',
                'doctor' => 'A fine idea! Show them what we can create!',
            ],
            'content_generation' => [
                'igor'   => 'Master! Shall Igor bring some test subjects... er, example content to life?',
                'doctor' => 'YES! We need specimens to demonstrate our creation!',
            ],
            'generating_content' => [
                'igor'   => 'Igor is gathering the parts... pages, posts, categories... 📚',
                'doctor' => 'Excellent work! Bring them ALL to life!',
            ],
            'content_complete' => [
                'igor'   => 'The specimens are ready, Master! All breathing and functional!',
                'doctor' => 'Magnificent! Now they can see what we\'ve created!',
            ],
            'success' => [
                'igor'   => "It's ALIVE, Master! The creation breathes!",
                'doctor' => 'SUCCESS! My masterpiece is COMPLETE! MUHAHAHA!',
            ],
            'error' => [
                'igor'   => 'Master! Something went wrong in the laboratory!',
                'doctor' => 'BLAST! We must investigate this failure!',
            ],
            'migration_success' => [
                'igor'   => 'All parts assembled perfectly, Master!',
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
            'theme_setup' => [
                'igor'   => 'Master, shall Igor prepare the panel theme for our creation?',
                'doctor' => 'The aesthetic! We must ensure our creation LOOKS the part!',
            ],
            'theme_exists' => [
                'igor'   => 'A theme already exists in this panel, Master!',
                'doctor' => 'Excellent! We shall enhance it with our own touch!',
            ],
            'theme_creating' => [
                'igor'   => 'Creating a new theme for the panel, Master...',
                'doctor' => 'A blank canvas for our masterpiece!',
            ],
            'theme_configured' => [
                'igor'   => 'The panel theme is configured for our creation, Master!',
                'doctor' => 'Now our creation will look MAGNIFICENT in the admin panel!',
            ],
            'og_image_offer' => [
                'igor'   => 'Master, shall Igor install the Ogre (spatie/laravel-og-image) to paint automatic portraits for every page?',
                'doctor' => 'A likeness, automatically rendered! Every creation deserves its portrait!',
            ],
            'og_image_installing' => [
                'igor'   => 'Fetching the image-conjuring elixir from the vault... 🧪',
                'doctor' => 'Let the machine SEE what we have created!',
            ],
            'og_image_configured' => [
                'igor'   => 'The Ogre is bound to our creation, Master! Portraits shall be painted automatically!',
                'doctor' => 'MAGNIFICENT! Every page now bears its own likeness!',
            ],
            'og_image_skip' => [
                'igor'   => 'As you wish, Master. Manual portraits will suffice for now — summon the Ogre later with: composer require spatie/laravel-og-image',
                'doctor' => 'No matter — we may summon the Ogre again another night!',
            ],
            'og_image_already_installed' => [
                'igor'   => 'Master! The Ogre already dwells within these walls!',
                'doctor' => 'Ah, it seems that work is already complete.',
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
    ██╗████████╗███████╗     █████╗ ██╗     ██╗██╗   ██╗███████╗██╗
    ██║╚══██╔══╝██╔════╝    ██╔══██╗██║     ██║██║   ██║██╔════╝██║
    ██║   ██║   ███████╗    ███████║██║     ██║██║   ██║█████╗  ██║
    ██║   ██║   ╚════██║    ██╔══██║██║     ██║╚██╗ ██╔╝██╔══╝  ╚═╝
    ██║   ██║   ███████║    ██║  ██║███████╗██║ ╚████╔╝ ███████╗██╗
    ╚═╝   ╚═╝   ╚══════╝    ╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝ ╚══════╝╚═╝
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

    /**
     * Follow-up instructions printed after spatie/laravel-og-image is installed
     */
    public static function ogImageFollowUp(): string
    {
        return <<<'TEXT'
Next steps for OG images:
   Map templates in config/franken-cms.php → og_image.templates
   Add <x-franken-og-image /> to your theme layout (already present if you installed the example theme)
   For Chrome-less hosts, set CLOUDFLARE_API_TOKEN and CLOUDFLARE_ACCOUNT_ID in your .env
TEXT;
    }
}
