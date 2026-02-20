<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\DirectiveProviderInterface;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Registries\FieldTypeRegistry;

class FieldTypeManager
{
    public function __construct(
        protected FieldTypeRegistry $registry
    ) {}

    /**
     * Register a field type and its directive provider
     */
    public function register(FieldTypeInterface $fieldType, DirectiveProviderInterface $provider): void
    {
        // Register in the registry
        $this->registry->register($fieldType);

        // Register the directives
        $provider->register();
    }

    /**
     * Register a field type without directives
     */
    public function registerFieldTypeOnly(FieldTypeInterface $fieldType): void
    {
        $this->registry->register($fieldType);
    }

    /**
     * Get the registry instance
     */
    public function getRegistry(): FieldTypeRegistry
    {
        return $this->registry;
    }

    /**
     * Check if a field type is registered
     */
    public function has(string $name): bool
    {
        return $this->registry->has($name);
    }

    /**
     * Get a field type by name
     */
    public function get(string $name): ?FieldTypeInterface
    {
        return $this->registry->get($name);
    }

    /**
     * Get all registered field types
     *
     * @return array<string, FieldTypeInterface>
     */
    public function all(): array
    {
        return $this->registry->all();
    }
}
