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

        // MS Tiles
        'mstile-70x70.png'   => [70, 70],
        'mstile-144x144.png' => [144, 144],
        'mstile-150x150.png' => [150, 150],
        'mstile-310x150.png' => [310, 150],
        'mstile-310x310.png' => [310, 310],
    ];

    /**
     * Generate all favicon sizes from a source image
     */
    public function generate(string $sourcePath): array
    {
        $publicPath = public_path();
        $generated = [];

        // Generate each size
        foreach ($this->sizes as $filename => $dimensions) {
            [$width, $height] = $dimensions;
            $outputPath = $publicPath . '/' . $filename;

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
            $ico32Path = $publicPath . '/favicon-32x32.png';
            $icoPath = $publicPath . '/favicon.ico';

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
        $publicPath = public_path();

        foreach (array_keys($this->sizes) as $filename) {
            $path = $publicPath . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Remove favicon.ico
        $icoPath = $publicPath . '/favicon.ico';
        if (file_exists($icoPath)) {
            unlink($icoPath);
        }
    }

    /**
     * Get HTML meta tags for favicons
     */
    public function getHtmlTags(): string
    {
        $html = [];

        // Apple Touch Icons
        $html[] = '<link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">';
        $html[] = '<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">';

        // Standard Favicons
        $html[] = '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">';
        $html[] = '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">';
        $html[] = '<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">';
        $html[] = '<link rel="icon" type="image/png" sizes="196x196" href="/favicon-196x196.png">';

        // MS Tiles
        $html[] = '<meta name="msapplication-TileColor" content="#ffffff">';
        $html[] = '<meta name="msapplication-TileImage" content="/mstile-144x144.png">';
        $html[] = '<meta name="msapplication-square70x70logo" content="/mstile-70x70.png">';
        $html[] = '<meta name="msapplication-square150x150logo" content="/mstile-150x150.png">';
        $html[] = '<meta name="msapplication-wide310x150logo" content="/mstile-310x150.png">';
        $html[] = '<meta name="msapplication-square310x310logo" content="/mstile-310x310.png">';

        return implode("\n    ", $html);
    }
}
