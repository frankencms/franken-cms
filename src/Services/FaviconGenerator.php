<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use Exception;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class FaviconGenerator
{
    /**
     * Favicon sizes to generate
     */
    protected array $sizes = [
        // Apple Touch Icons
        'apple-touch-icon-57x57.png'   => [57, 57],
        'apple-touch-icon-60x60.png'   => [60, 60],
        'apple-touch-icon-72x72.png'   => [72, 72],
        'apple-touch-icon-76x76.png'   => [76, 76],
        'apple-touch-icon-114x114.png' => [114, 114],
        'apple-touch-icon-120x120.png' => [120, 120],
        'apple-touch-icon-144x144.png' => [144, 144],
        'apple-touch-icon-152x152.png' => [152, 152],

        // Standard Favicons
        'favicon-16x16.png'   => [16, 16],
        'favicon-32x32.png'   => [32, 32],
        'favicon-96x96.png'   => [96, 96],
        'favicon-128.png'     => [128, 128],
        'favicon-196x196.png' => [196, 196],
    ];

    /**
     * Generate all favicon sizes from a source image
     */
    public function generate(string $sourcePath): array
    {
        $storagePath = storage_path('app/public/favicons');
        $generated = [];

        // Ensure the favicons directory exists
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Generate each size
        foreach ($this->sizes as $filename => $dimensions) {
            [$width, $height] = $dimensions;
            $outputPath = $storagePath . '/' . $filename;

            try {
                Image::load($sourcePath)
                    ->fit(Fit::Contain, $width, $height)
                    ->format('png')
                    ->save($outputPath);

                $generated[] = $filename;
            } catch (Exception $e) {
                // Log error but continue with other sizes
                logger()->error("Failed to generate favicon {$filename}: " . $e->getMessage());
            }
        }

        // Generate favicon.ico (multi-size ICO file from 32x32 PNG)
        try {
            $ico32Path = $storagePath . '/favicon-32x32.png';
            $icoPath = $storagePath . '/favicon.ico';

            if (file_exists($ico32Path)) {
                // Copy 32x32 as ICO (browsers handle PNG in ICO format)
                copy($ico32Path, $icoPath);
                $generated[] = 'favicon.ico';
            }
        } catch (Exception $e) {
            logger()->error('Failed to generate favicon.ico: ' . $e->getMessage());
        }

        return $generated;
    }

    /**
     * Clear all generated favicons
     */
    public function clear(): void
    {
        $storagePath = storage_path('app/public/favicons');

        foreach (array_keys($this->sizes) as $filename) {
            $path = $storagePath . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Remove favicon.ico
        $icoPath = $storagePath . '/favicon.ico';
        if (file_exists($icoPath)) {
            unlink($icoPath);
        }
    }

    /**
     * Generated favicon files that exist on disk, mapped to a "WxH" sizes
     * attribute (null for favicon.ico).
     *
     * @return array<string, string|null>
     */
    public function generatedFiles(): array
    {
        $storagePath = storage_path('app/public/favicons');
        $files = [];

        foreach ($this->sizes as $filename => [$width, $height]) {
            if (file_exists("{$storagePath}/{$filename}")) {
                $files[$filename] = "{$width}x{$height}";
            }
        }

        if (file_exists("{$storagePath}/favicon.ico")) {
            $files['favicon.ico'] = null;
        }

        return $files;
    }
}
