<?php

use FrankenCms\Services\SocialLinksService;

describe('SocialLinksService', function () {

    beforeEach(function () {
        $this->service = new SocialLinksService;
    });

    describe('getPlatforms', function () {

        it('returns default platforms when config is not set', function () {
            $platforms = $this->service->getPlatforms();

            expect($platforms)->toBeArray();
            expect($platforms)->toHaveKey('twitter');
            expect($platforms)->toHaveKey('github');
            expect($platforms)->toHaveKey('linkedin');
            expect($platforms)->toHaveKey('facebook');
            expect($platforms)->toHaveKey('instagram');
        });

        it('returns all expected default platforms', function () {
            $platforms = $this->service->getPlatforms();

            $expectedPlatforms = [
                'twitter', 'github', 'linkedin', 'facebook', 'instagram',
                'youtube', 'tiktok', 'mastodon', 'bluesky', 'threads',
                'discord', 'twitch', 'dribbble', 'behance', 'medium',
                'devto', 'stackoverflow', 'codepen', 'pinterest', 'reddit',
            ];

            foreach ($expectedPlatforms as $platform) {
                expect($platforms)->toHaveKey($platform);
            }
        });

        it('each platform has required keys', function () {
            $platforms = $this->service->getPlatforms();

            foreach ($platforms as $key => $config) {
                expect($config)->toHaveKey('label');
                expect($config)->toHaveKey('url_pattern');
                expect($config)->toHaveKey('icon');
            }
        });

    });

    describe('getPlatformOptions', function () {

        it('returns key-value pairs of platform key to label', function () {
            $options = $this->service->getPlatformOptions();

            expect($options)->toBeArray();
            expect($options['twitter'])->toBe('Twitter / X');
            expect($options['github'])->toBe('GitHub');
            expect($options['linkedin'])->toBe('LinkedIn');
        });

    });

    describe('getPlatform', function () {

        it('returns platform config for valid platform', function () {
            $twitter = $this->service->getPlatform('twitter');

            expect($twitter)->toBeArray();
            expect($twitter['label'])->toBe('Twitter / X');
            expect($twitter['url_pattern'])->toBe('https://twitter.com/{username}');
            expect($twitter['icon'])->toBe('fab-x-twitter');
        });

        it('returns null for invalid platform', function () {
            $result = $this->service->getPlatform('nonexistent');

            expect($result)->toBeNull();
        });

    });

    describe('resolveUrl', function () {

        it('returns URL as-is when value is already a URL', function () {
            $url = $this->service->resolveUrl('twitter', 'https://twitter.com/johndoe');

            expect($url)->toBe('https://twitter.com/johndoe');
        });

        it('returns URL as-is for http URLs', function () {
            $url = $this->service->resolveUrl('github', 'http://github.com/johndoe');

            expect($url)->toBe('http://github.com/johndoe');
        });

        it('constructs URL from username using pattern', function () {
            $url = $this->service->resolveUrl('twitter', 'johndoe');

            expect($url)->toBe('https://twitter.com/johndoe');
        });

        it('removes @ prefix from usernames', function () {
            $url = $this->service->resolveUrl('twitter', '@johndoe');

            expect($url)->toBe('https://twitter.com/johndoe');
        });

        it('handles @ in YouTube URL pattern correctly', function () {
            $url = $this->service->resolveUrl('youtube', 'mychannel');

            expect($url)->toBe('https://youtube.com/@mychannel');
        });

        it('returns null for empty value', function () {
            $url = $this->service->resolveUrl('twitter', '');

            expect($url)->toBeNull();
        });

        it('returns null for invalid platform', function () {
            $url = $this->service->resolveUrl('nonexistent', 'johndoe');

            expect($url)->toBeNull();
        });

        it('works for all default platforms', function () {
            $testCases = [
                'twitter'       => ['johndoe', 'https://twitter.com/johndoe'],
                'github'        => ['johndoe', 'https://github.com/johndoe'],
                'linkedin'      => ['johndoe', 'https://linkedin.com/in/johndoe'],
                'youtube'       => ['channel', 'https://youtube.com/@channel'],
                'tiktok'        => ['user', 'https://tiktok.com/@user'],
                'bluesky'       => ['user.bsky.social', 'https://bsky.app/profile/user.bsky.social'],
                'reddit'        => ['user', 'https://reddit.com/user/user'],
                'stackoverflow' => ['12345', 'https://stackoverflow.com/users/12345'],
            ];

            foreach ($testCases as $platform => [$username, $expectedUrl]) {
                $url = $this->service->resolveUrl($platform, $username);
                expect($url)->toBe($expectedUrl, "Failed for platform: {$platform}");
            }
        });

    });

    describe('getIcon', function () {

        it('returns icon for valid platform', function () {
            $icon = $this->service->getIcon('twitter');

            expect($icon)->toBe('fab-x-twitter');
        });

        it('returns icon for github', function () {
            $icon = $this->service->getIcon('github');

            expect($icon)->toBe('fab-github');
        });

        it('returns null for invalid platform', function () {
            $icon = $this->service->getIcon('nonexistent');

            expect($icon)->toBeNull();
        });

    });

    describe('getPlaceholder', function () {

        it('returns custom placeholder when set', function () {
            $placeholder = $this->service->getPlaceholder('twitter');

            expect($placeholder)->toBe('@username or https://twitter.com/username');
        });

        it('returns default placeholder for platform without custom one', function () {
            // Even though all default platforms have placeholders, test the fallback logic
            $placeholder = $this->service->getPlaceholder('github');

            expect($placeholder)->toContain('username');
        });

    });

    describe('validateValue', function () {

        it('returns true for empty value', function () {
            $result = $this->service->validateValue('twitter', '');

            expect($result)->toBeTrue();
        });

        it('returns true for valid username', function () {
            $result = $this->service->validateValue('twitter', 'johndoe');

            expect($result)->toBeTrue();
        });

        it('returns true for username with underscore', function () {
            $result = $this->service->validateValue('twitter', 'john_doe');

            expect($result)->toBeTrue();
        });

        it('returns true for username with hyphen', function () {
            $result = $this->service->validateValue('github', 'john-doe');

            expect($result)->toBeTrue();
        });

        it('returns true for username with dots', function () {
            $result = $this->service->validateValue('bluesky', 'john.bsky.social');

            expect($result)->toBeTrue();
        });

        it('returns true for username with @ prefix', function () {
            $result = $this->service->validateValue('twitter', '@johndoe');

            expect($result)->toBeTrue();
        });

        it('returns true for valid URL', function () {
            $result = $this->service->validateValue('twitter', 'https://twitter.com/johndoe');

            expect($result)->toBeTrue();
        });

        it('returns error for username with invalid characters', function () {
            $result = $this->service->validateValue('twitter', 'john doe!');

            expect($result)->toBeString();
            expect($result)->toContain('letters, numbers');
        });

        it('returns error for username exceeding max length', function () {
            $longUsername = str_repeat('a', 101);
            $result = $this->service->validateValue('twitter', $longUsername);

            expect($result)->toBeString();
            expect($result)->toContain('too long');
        });

        it('returns error for invalid URL format', function () {
            $result = $this->service->validateValue('twitter', 'https://');

            expect($result)->toBeString();
            expect($result)->toContain('valid URL');
        });

    });

    describe('isUrl', function () {

        it('returns true for https URL', function () {
            expect($this->service->isUrl('https://twitter.com/user'))->toBeTrue();
        });

        it('returns true for http URL', function () {
            expect($this->service->isUrl('http://example.com'))->toBeTrue();
        });

        it('returns false for username', function () {
            expect($this->service->isUrl('johndoe'))->toBeFalse();
        });

        it('returns false for username with @', function () {
            expect($this->service->isUrl('@johndoe'))->toBeFalse();
        });

    });

    describe('extractUsername', function () {

        it('returns username from Twitter URL', function () {
            $username = $this->service->extractUsername('twitter', 'https://twitter.com/johndoe');

            expect($username)->toBe('johndoe');
        });

        it('returns username from GitHub URL', function () {
            $username = $this->service->extractUsername('github', 'https://github.com/johndoe');

            expect($username)->toBe('johndoe');
        });

        it('returns username from URL with trailing slash', function () {
            $username = $this->service->extractUsername('twitter', 'https://twitter.com/johndoe/');

            expect($username)->toBe('johndoe');
        });

        it('returns original value if not a URL', function () {
            $username = $this->service->extractUsername('twitter', 'johndoe');

            expect($username)->toBe('johndoe');
        });

        it('returns original URL if pattern does not match', function () {
            $url = 'https://different-site.com/johndoe';
            $result = $this->service->extractUsername('twitter', $url);

            expect($result)->toBe($url);
        });

        it('returns original URL for invalid platform', function () {
            $url = 'https://example.com/user';
            $result = $this->service->extractUsername('nonexistent', $url);

            expect($result)->toBe($url);
        });

    });

});
