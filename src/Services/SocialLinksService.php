<?php

declare(strict_types=1);

namespace FrankenCms\Services;

class SocialLinksService
{
    /**
     * Get all configured social platforms
     *
     * @return array<string, array{label: string, url_pattern: string, icon: string|null, placeholder?: string}>
     */
    public function getPlatforms(): array
    {
        return config('franken-cms.social_platforms', $this->getDefaultPlatforms());
    }

    /**
     * Get platform options for select/dropdown fields
     *
     * @return array<string, string>
     */
    public function getPlatformOptions(): array
    {
        $platforms = $this->getPlatforms();
        $options = [];

        foreach ($platforms as $key => $config) {
            $options[$key] = $config['label'];
        }

        return $options;
    }

    /**
     * Get a specific platform configuration
     *
     * @return array{label: string, url_pattern: string, icon: string|null, placeholder?: string}|null
     */
    public function getPlatform(string $platform): ?array
    {
        return $this->getPlatforms()[$platform] ?? null;
    }

    /**
     * Resolve a platform value to a full URL
     *
     * If the value is already a URL (starts with http:// or https://), returns it as-is.
     * Otherwise, treats it as a username and constructs the URL using the platform's pattern.
     */
    public function resolveUrl(string $platform, string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // If it's already a URL, return it
        if ($this->isUrl($value)) {
            return $value;
        }

        // Get the platform config
        $config = $this->getPlatform($platform);
        if (! $config || empty($config['url_pattern'])) {
            return null;
        }

        // Clean the username (remove @ prefix if present)
        $username = ltrim($value, '@');

        // Replace {username} placeholder in the pattern
        return str_replace('{username}', $username, $config['url_pattern']);
    }

    /**
     * Get the icon component name for a platform
     *
     * Returns the Blade icon component name (e.g., 'fab-twitter', 'heroicon-o-link')
     * or null if no icon is configured.
     */
    public function getIcon(string $platform): ?string
    {
        $config = $this->getPlatform($platform);

        return $config['icon'] ?? null;
    }

    /**
     * Get the placeholder text for a platform input
     */
    public function getPlaceholder(string $platform): string
    {
        $config = $this->getPlatform($platform);

        if (! empty($config['placeholder'])) {
            return $config['placeholder'];
        }

        // Generate default placeholder from URL pattern
        if (! empty($config['url_pattern'])) {
            $pattern = $config['url_pattern'];
            // Replace {username} with example
            return str_replace('{username}', 'username', $pattern) . ' or @username';
        }

        return 'Enter username or URL';
    }

    /**
     * Validate a value for a specific platform
     *
     * Returns true if valid, or an error message string if invalid.
     */
    public function validateValue(string $platform, string $value): true | string
    {
        if (empty($value)) {
            return true; // Empty is allowed (optional field)
        }

        // If it's a URL, validate URL format
        if ($this->isUrl($value)) {
            if (! filter_var($value, FILTER_VALIDATE_URL)) {
                return 'Please enter a valid URL.';
            }

            return true;
        }

        // Validate username format (alphanumeric, underscores, hyphens, dots, @)
        $username = ltrim($value, '@');
        if (! preg_match('/^[\w\-.]+$/u', $username)) {
            return 'Username can only contain letters, numbers, underscores, hyphens, and dots.';
        }

        // Check username length
        if (strlen($username) > 100) {
            return 'Username is too long (max 100 characters).';
        }

        return true;
    }

    /**
     * Check if a value looks like a URL
     */
    public function isUrl(string $value): bool
    {
        return (bool) preg_match('/^https?:\/\//i', $value);
    }

    /**
     * Extract username from a URL if possible
     *
     * Attempts to parse the URL and extract the username portion.
     * Returns the original value if extraction fails.
     */
    public function extractUsername(string $platform, string $url): string
    {
        if (! $this->isUrl($url)) {
            return $url;
        }

        $config = $this->getPlatform($platform);
        if (! $config || empty($config['url_pattern'])) {
            return $url;
        }

        // Convert pattern to regex
        $pattern = preg_quote($config['url_pattern'], '/');
        $pattern = str_replace('\{username\}', '([^\/\?]+)', $pattern);

        // Support both http and https
        $pattern = str_replace('https\\:', 'https?\\:', $pattern);

        // Remove trailing slash requirement
        $pattern = rtrim($pattern, '\/');
        $pattern = '/^' . $pattern . '\/?$/i';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return $url;
    }

    /**
     * Get the default platform configurations
     *
     * @return array<string, array{label: string, url_pattern: string, icon: string|null, placeholder?: string}>
     */
    protected function getDefaultPlatforms(): array
    {
        return [
            'twitter' => [
                'label'       => 'Twitter / X',
                'url_pattern' => 'https://twitter.com/{username}',
                'icon'        => 'fab-x-twitter',
                'placeholder' => '@username or https://twitter.com/username',
            ],
            'github' => [
                'label'       => 'GitHub',
                'url_pattern' => 'https://github.com/{username}',
                'icon'        => 'fab-github',
                'placeholder' => 'username or https://github.com/username',
            ],
            'linkedin' => [
                'label'       => 'LinkedIn',
                'url_pattern' => 'https://linkedin.com/in/{username}',
                'icon'        => 'fab-linkedin',
                'placeholder' => 'username or https://linkedin.com/in/username',
            ],
            'facebook' => [
                'label'       => 'Facebook',
                'url_pattern' => 'https://facebook.com/{username}',
                'icon'        => 'fab-facebook',
                'placeholder' => 'username or https://facebook.com/username',
            ],
            'instagram' => [
                'label'       => 'Instagram',
                'url_pattern' => 'https://instagram.com/{username}',
                'icon'        => 'fab-instagram',
                'placeholder' => '@username or https://instagram.com/username',
            ],
            'youtube' => [
                'label'       => 'YouTube',
                'url_pattern' => 'https://youtube.com/@{username}',
                'icon'        => 'fab-youtube',
                'placeholder' => '@username or https://youtube.com/@username',
            ],
            'tiktok' => [
                'label'       => 'TikTok',
                'url_pattern' => 'https://tiktok.com/@{username}',
                'icon'        => 'fab-tiktok',
                'placeholder' => '@username or https://tiktok.com/@username',
            ],
            'mastodon' => [
                'label'       => 'Mastodon',
                'url_pattern' => 'https://mastodon.social/@{username}',
                'icon'        => 'fab-mastodon',
                'placeholder' => 'Full URL (e.g., https://mastodon.social/@username)',
            ],
            'bluesky' => [
                'label'       => 'Bluesky',
                'url_pattern' => 'https://bsky.app/profile/{username}',
                'icon'        => 'fab-bluesky',
                'placeholder' => 'username.bsky.social or https://bsky.app/profile/...',
            ],
            'threads' => [
                'label'       => 'Threads',
                'url_pattern' => 'https://threads.net/@{username}',
                'icon'        => 'fab-threads',
                'placeholder' => '@username or https://threads.net/@username',
            ],
            'discord' => [
                'label'       => 'Discord',
                'url_pattern' => 'https://discord.gg/{username}',
                'icon'        => 'fab-discord',
                'placeholder' => 'Invite code or https://discord.gg/...',
            ],
            'twitch' => [
                'label'       => 'Twitch',
                'url_pattern' => 'https://twitch.tv/{username}',
                'icon'        => 'fab-twitch',
                'placeholder' => 'username or https://twitch.tv/username',
            ],
            'dribbble' => [
                'label'       => 'Dribbble',
                'url_pattern' => 'https://dribbble.com/{username}',
                'icon'        => 'fab-dribbble',
                'placeholder' => 'username or https://dribbble.com/username',
            ],
            'behance' => [
                'label'       => 'Behance',
                'url_pattern' => 'https://behance.net/{username}',
                'icon'        => 'fab-behance',
                'placeholder' => 'username or https://behance.net/username',
            ],
            'medium' => [
                'label'       => 'Medium',
                'url_pattern' => 'https://medium.com/@{username}',
                'icon'        => 'fab-medium',
                'placeholder' => '@username or https://medium.com/@username',
            ],
            'devto' => [
                'label'       => 'DEV.to',
                'url_pattern' => 'https://dev.to/{username}',
                'icon'        => 'fab-dev',
                'placeholder' => 'username or https://dev.to/username',
            ],
            'stackoverflow' => [
                'label'       => 'Stack Overflow',
                'url_pattern' => 'https://stackoverflow.com/users/{username}',
                'icon'        => 'fab-stack-overflow',
                'placeholder' => 'user-id or https://stackoverflow.com/users/...',
            ],
            'codepen' => [
                'label'       => 'CodePen',
                'url_pattern' => 'https://codepen.io/{username}',
                'icon'        => 'fab-codepen',
                'placeholder' => 'username or https://codepen.io/username',
            ],
            'pinterest' => [
                'label'       => 'Pinterest',
                'url_pattern' => 'https://pinterest.com/{username}',
                'icon'        => 'fab-pinterest',
                'placeholder' => 'username or https://pinterest.com/username',
            ],
            'reddit' => [
                'label'       => 'Reddit',
                'url_pattern' => 'https://reddit.com/user/{username}',
                'icon'        => 'fab-reddit',
                'placeholder' => 'username or https://reddit.com/user/username',
            ],
        ];
    }
}
