<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Settings\RobotsSettings;

class RobotsService
{
    public function __construct(
        protected RobotsSettings $settings,
        protected ?SitemapService $sitemapService = null
    ) {}

    /**
     * Check if a static robots.txt file exists in the public directory
     */
    public function hasStaticFile(): bool
    {
        return file_exists(public_path('robots.txt'));
    }

    /**
     * Get the contents of the static robots.txt file
     */
    public function getStaticContent(): ?string
    {
        if (! $this->hasStaticFile()) {
            return null;
        }

        return file_get_contents(public_path('robots.txt'));
    }

    /**
     * Generate dynamic robots.txt content from settings
     */
    public function generate(): string
    {
        // If dynamic generation is disabled, return empty
        if (! $this->settings->enabled) {
            return '';
        }

        $lines = [];

        // Add user agent rules
        foreach ($this->settings->user_agents as $agentConfig) {
            $userAgent = $agentConfig['user_agent'] ?? '*';
            $rules = $agentConfig['rules'] ?? [];
            $crawlDelay = $agentConfig['crawl_delay'] ?? null;

            // Start a new user agent section
            $lines[] = "User-agent: {$userAgent}";

            // Add rules for this user agent
            foreach ($rules as $rule) {
                $parsedRule = $this->parseRule($rule);
                if ($parsedRule) {
                    $lines[] = $parsedRule;
                }
            }

            // Add crawl delay if specified
            if ($crawlDelay !== null && $crawlDelay > 0) {
                $lines[] = "Crawl-delay: {$crawlDelay}";
            }

            // Add blank line after each user agent section
            $lines[] = '';
        }

        // Add sitemaps
        $sitemaps = $this->getSitemapUrls();
        foreach ($sitemaps as $sitemap) {
            $lines[] = "Sitemap: {$sitemap}";
        }

        // Add host directive if specified
        if ($this->settings->host) {
            $lines[] = '';
            $lines[] = "Host: {$this->settings->host}";
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Get the robots.txt content (static if exists, otherwise dynamic)
     */
    public function getContent(): string
    {
        // Check for static file first
        if ($this->hasStaticFile()) {
            return $this->getStaticContent();
        }

        // Generate dynamic content
        return $this->generate();
    }

    /**
     * Parse and normalize a robots.txt rule
     */
    protected function parseRule(string $rule): ?string
    {
        // Parse the rule (e.g., "Disallow: /admin" or "Allow: /public")
        $parts = explode(':', $rule, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $directive = trim($parts[0]);
        $path = trim($parts[1]);

        // Normalize directive casing
        $directive = ucfirst(strtolower($directive));

        // Only allow valid directives
        if (! in_array($directive, ['Allow', 'Disallow'])) {
            return null;
        }

        return "{$directive}: {$path}";
    }

    /**
     * Get all sitemap URLs (auto-generated + additional)
     */
    protected function getSitemapUrls(): array
    {
        $sitemaps = [];

        // Add auto-generated sitemaps if SitemapService is available
        if ($this->sitemapService && $this->sitemapService->isEnabled()) {
            $sitemaps[] = url('/sitemap.xml');
        }

        // Add additional configured sitemaps
        foreach ($this->settings->additional_sitemaps as $sitemap) {
            // Convert relative URLs to absolute
            if (! str_starts_with($sitemap, 'http')) {
                $sitemap = url($sitemap);
            }
            $sitemaps[] = $sitemap;
        }

        return array_unique($sitemaps);
    }
}
