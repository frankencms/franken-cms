<?php

namespace FrankenCms\Registries;

use FrankenCms\Contracts\SettingsTabProviderInterface;
use Illuminate\Support\Collection;

class SettingsTabRegistry
{
    /** @var Collection<SettingsTabProviderInterface> */
    protected Collection $providers;

    public function __construct()
    {
        $this->providers = collect();
    }

    /**
     * Register a settings tab provider
     */
    public function register(SettingsTabProviderInterface $provider): void
    {
        $this->providers->put($provider->getTabKey(), $provider);
    }

    /**
     * Get all registered tab providers ordered by priority
     */
    public function getProviders(): Collection
    {
        return $this->providers->sortBy(fn (SettingsTabProviderInterface $provider) => $provider->getOrder());
    }

    /**
     * Get a specific provider by key
     */
    public function getProvider(string $key): ?SettingsTabProviderInterface
    {
        return $this->providers->get($key);
    }

    /**
     * Check if a provider is registered
     */
    public function hasProvider(string $key): bool
    {
        return $this->providers->has($key);
    }

    /**
     * Get all tabs ordered by priority
     */
    public function getTabs(): array
    {
        return $this->getProviders()
            ->map(fn (SettingsTabProviderInterface $provider) => $provider->getTab())
            ->values()
            ->toArray();
    }

    /**
     * Get all settings classes from registered providers
     */
    public function getSettingsClasses(): array
    {
        return $this->getProviders()
            ->map(fn (SettingsTabProviderInterface $provider) => $provider->getSettingsClass())
            ->values()
            ->toArray();
    }
}
