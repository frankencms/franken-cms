<?php

use Illuminate\Support\Facades\Route;

/**
 * Favicon Routes
 *
 * Serve favicon files from storage instead of public directory
 * This allows favicons to be backed up with storage folder
 */
Route::get('/{filename}', function (string $filename) {
    $allowedFiles = [
        // Apple Touch Icons
        'apple-touch-icon-57x57.png',
        'apple-touch-icon-60x60.png',
        'apple-touch-icon-72x72.png',
        'apple-touch-icon-76x76.png',
        'apple-touch-icon-114x114.png',
        'apple-touch-icon-120x120.png',
        'apple-touch-icon-144x144.png',
        'apple-touch-icon-152x152.png',

        // Standard Favicons
        'favicon-16x16.png',
        'favicon-32x32.png',
        'favicon-96x96.png',
        'favicon-128.png',
        'favicon-196x196.png',
        'favicon.ico',

        // MS Tiles
        'mstile-70x70.png',
        'mstile-144x144.png',
        'mstile-150x150.png',
        'mstile-310x150.png',
        'mstile-310x310.png',
    ];

    // Validate filename is in allowed list
    if (! in_array($filename, $allowedFiles)) {
        abort(404);
    }

    $path = storage_path('app/public/favicons/' . $filename);

    if (! file_exists($path)) {
        abort(404);
    }

    // Determine content type
    $mimeType = $filename === 'favicon.ico' ? 'image/x-icon' : 'image/png';

    return response()->file($path, [
        'Content-Type'  => $mimeType,
        'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
    ]);
})->where('filename', implode('|', [
    'apple-touch-icon-[0-9]+x[0-9]+\.png',
    'favicon-[0-9]+x[0-9]+\.png',
    'favicon-[0-9]+\.png',
    'favicon\.ico',
    'mstile-[0-9]+x[0-9]+\.png',
]));
