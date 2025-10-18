<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Registries\SettingsTabRegistry;
use FrankenCms\SettingsTabs\AiSettingsTabProvider;
use FrankenCms\SettingsTabs\GeneralSettingsTabProvider;
use FrankenCms\SettingsTabs\MediaSettingsTabProvider;
use FrankenCms\SettingsTabs\PermalinkSettingsTabProvider;
use FrankenCms\SettingsTabs\ReadingSettingsTabProvider;
use FrankenCms\SettingsTabs\RobotsSettingsTabProvider;
use FrankenCms\SettingsTabs\SeoSettingsTabProvider;
use FrankenCms\SettingsTabs\SitemapSettingsTabProvider;
use FrankenCms\SettingsTabs\StackSettingsTabProvider;

class SettingsTabService
{
    public function __construct(
        protected SettingsTabRegistry $registry
    ) {}

    /**
     * Register all default CMS settings tabs
     */
    public function registerDefaultTabs(): void
    {
        $this->registry->register(new GeneralSettingsTabProvider);
        $this->registry->register(new ReadingSettingsTabProvider);
        $this->registry->register(new MediaSettingsTabProvider);
        $this->registry->register(new PermalinkSettingsTabProvider);
        $this->registry->register(new SeoSettingsTabProvider);
        $this->registry->register(new RobotsSettingsTabProvider);
        $this->registry->register(new SitemapSettingsTabProvider);
        $this->registry->register(new StackSettingsTabProvider);
        $this->registry->register(new AiSettingsTabProvider);

        // TODO: Add other tab providers here as they're created
        // $this->registry->register(new WritingSettingsTabProvider());
        // $this->registry->register(new DiscussionSettingsTabProvider());
        // $this->registry->register(new PrivacySettingsTabProvider());
    }

    /**
     * Register a custom tab provider (for external packages)
     */
    public function registerTab(SettingsTabProviderInterface $provider): void
    {
        $this->registry->register($provider);
    }

    /**
     * Get the registry instance
     */
    public function getRegistry(): SettingsTabRegistry
    {
        return $this->registry;
    }
}
