<?php

test('every package migration is registered in the hasMigrations publish list', function () {
    $onDisk = collect(glob(__DIR__ . '/../../database/migrations/*.php'))
        ->map(fn (string $path) => basename($path, '.php'))
        ->sort()
        ->values();

    $providerSource = file_get_contents(__DIR__ . '/../../src/FrankenCmsServiceProvider.php');
    preg_match('/->hasMigrations\(\[(.*?)\]\)/s', $providerSource, $matches);
    preg_match_all("/'([^']+)'/", $matches[1] ?? '', $registered);

    $publishList = collect($registered[1] ?? [])->sort()->values();

    expect($publishList->all())->toBe(
        $onDisk->all(),
        'database/migrations and FrankenCmsServiceProvider::hasMigrations() are out of sync — '
        . 'consuming apps only receive migrations named in hasMigrations().'
    );
});
