<?php

namespace FrankenCms\Contracts;

use Filament\Schemas\Components\Tabs\Tab;

interface SettingsTabProviderInterface
{
    /**
     * Get the tab configuration for this settings provider
     */
    public function getTab(): Tab;

    /**
     * Get the settings class for this provider
     */
    public function getSettingsClass(): string;

    /**
     * Get the order/priority for this tab (lower numbers appear first)
     */
    public function getOrder(): int;

    /**
     * Get the tab identifier/key
     */
    public function getTabKey(): string;
}
