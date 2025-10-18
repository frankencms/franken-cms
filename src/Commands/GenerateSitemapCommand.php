<?php

declare(strict_types=1);

namespace FrankenCms\Commands;

use FrankenCms\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'franken-cms:generate-sitemap
                            {--force : Force generation even if disabled in settings}';

    protected $description = 'Generate XML sitemap for the site';

    public function handle(SitemapService $sitemapService): int
    {
        if (! $sitemapService->isEnabled() && ! $this->option('force')) {
            $this->error('Sitemap generation is disabled in settings.');
            $this->info('Use --force to generate anyway.');
            return self::FAILURE;
        }

        $this->info('Generating sitemap...');

        try {
            $sitemapService->writeToFile();
            $this->info('✓ Sitemap generated successfully at public/sitemap.xml');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate sitemap: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
