<?php

use FrankenCms\Support\IgorMessages;

describe('loadingMessages', function () {
    test('returns a non-empty array', function () {
        $messages = IgorMessages::loadingMessages();

        expect($messages)->toBeArray()->not->toBeEmpty();
    });

    test('all messages are strings', function () {
        $messages = IgorMessages::loadingMessages();

        foreach ($messages as $message) {
            expect($message)->toBeString()->not->toBeEmpty();
        }
    });
});

describe('randomLoadingMessage', function () {
    test('returns a string from the loading messages', function () {
        $message = IgorMessages::randomLoadingMessage();
        $allMessages = IgorMessages::loadingMessages();

        expect($message)->toBeString();
        expect($allMessages)->toContain($message);
    });
});

describe('installationMessages', function () {
    test('returns a non-empty array', function () {
        $messages = IgorMessages::installationMessages();

        expect($messages)->toBeArray()->not->toBeEmpty();
    });

    test('each step has igor and doctor messages', function () {
        $messages = IgorMessages::installationMessages();

        foreach ($messages as $step => $characters) {
            expect($characters)->toHaveKeys(['igor', 'doctor'], "Step '{$step}' missing igor or doctor key");
            expect($characters['igor'])->toBeString()->not->toBeEmpty();
            expect($characters['doctor'])->toBeString()->not->toBeEmpty();
        }
    });

    test('contains expected installation steps', function () {
        $messages = IgorMessages::installationMessages();

        $expectedSteps = [
            'welcome', 'publishing_config', 'publishing_migrations',
            'running_migrations', 'detecting_panels', 'registering_plugin',
            'success', 'error', 'og_image_offer', 'og_image_installing',
            'og_image_configured', 'og_image_skip', 'og_image_already_installed',
        ];

        foreach ($expectedSteps as $step) {
            expect($messages)->toHaveKey($step);
        }
    });
});

describe('installMessage', function () {
    test('returns igor message for a valid step', function () {
        $message = IgorMessages::installMessage('welcome', 'igor');

        expect($message)->toBeString()->not->toBeEmpty();
    });

    test('returns doctor message for a valid step', function () {
        $message = IgorMessages::installMessage('welcome', 'doctor');

        expect($message)->toBeString()->not->toBeEmpty();
    });

    test('defaults to igor character', function () {
        $igorMessage = IgorMessages::installMessage('welcome');
        $explicitIgor = IgorMessages::installMessage('welcome', 'igor');

        expect($igorMessage)->toBe($explicitIgor);
    });

    test('returns empty string for invalid step', function () {
        $message = IgorMessages::installMessage('nonexistent_step');

        expect($message)->toBe('');
    });

    test('returns empty string for invalid character', function () {
        $message = IgorMessages::installMessage('welcome', 'monster');

        expect($message)->toBe('');
    });
});

describe('asciiArt', function () {
    test('returns welcome art by default', function () {
        $art = IgorMessages::asciiArt();

        expect($art)->toBeString()->not->toBeEmpty();
        expect($art)->toContain('╔');
    });

    test('returns success art', function () {
        $art = IgorMessages::asciiArt('success');

        expect($art)->toBeString()->not->toBeEmpty();
        expect($art)->toContain('██');
    });

    test('returns lightning art', function () {
        $art = IgorMessages::asciiArt('lightning');

        expect($art)->toBeString()->not->toBeEmpty();
    });

    test('returns igor art', function () {
        $art = IgorMessages::asciiArt('igor');

        expect($art)->toContain('Yes, Master');
    });

    test('returns doctor art', function () {
        $art = IgorMessages::asciiArt('doctor');

        expect($art)->toContain('MAGNIFICENT');
    });

    test('returns empty string for unknown type', function () {
        $art = IgorMessages::asciiArt('unknown');

        expect($art)->toBe('');
    });
});

describe('ogImageFollowUp', function () {
    test('returns a non-empty string', function () {
        $text = IgorMessages::ogImageFollowUp();

        expect($text)->toBeString()->not->toBeEmpty();
    });

    test('mentions the config templates mapping, the blade component, and the Cloudflare env vars', function () {
        $text = IgorMessages::ogImageFollowUp();

        expect($text)->toContain('og_image.templates');
        expect($text)->toContain('<x-franken-og-image />');
        expect($text)->toContain('CLOUDFLARE_API_TOKEN');
        expect($text)->toContain('CLOUDFLARE_ACCOUNT_ID');
    });
});
