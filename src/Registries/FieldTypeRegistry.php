<?php

namespace FrankenCms\Registries;

use FrankenCms\Contracts\FieldTypeInterface;
use InvalidArgumentException;

class FieldTypeRegistry
{
    /**
     * @var array<string, FieldTypeInterface>
     */
    protected array $fieldTypes = [];

    /**
     * @var array<string, string> Map of directive names to field type names
     */
    protected array $directiveMap = [];

    /**
     * Register a field type
     */
    public function register(FieldTypeInterface $fieldType): void
    {
        $name = $fieldType->getName();

        if (isset($this->fieldTypes[$name])) {
            throw new InvalidArgumentException("Field type '{$name}' is already registered");
        }

        $this->fieldTypes[$name] = $fieldType;

        // Map directive names to this field type
        foreach ($fieldType->getDirectiveNames() as $directiveName) {
            if (isset($this->directiveMap[$directiveName])) {
                throw new InvalidArgumentException(
                    "Directive name '{$directiveName}' is already registered for field type '{$this->directiveMap[$directiveName]}'"
                );
            }
            $this->directiveMap[$directiveName] = $name;
        }
    }

    /**
     * Get a field type by its name
     */
    public function get(string $name): ?FieldTypeInterface
    {
        return $this->fieldTypes[$name] ?? null;
    }

    /**
     * Get a field type by directive name
     */
    public function getByDirective(string $directiveName): ?FieldTypeInterface
    {
        $fieldTypeName = $this->directiveMap[$directiveName] ?? null;

        if ($fieldTypeName === null) {
            return null;
        }

        return $this->get($fieldTypeName);
    }

    /**
     * Check if a field type is registered
     */
    public function has(string $name): bool
    {
        return isset($this->fieldTypes[$name]);
    }

    /**
     * Check if a directive name is registered
     */
    public function hasDirective(string $directiveName): bool
    {
        return isset($this->directiveMap[$directiveName]);
    }

    /**
     * Get all registered field types
     *
     * @return array<string, FieldTypeInterface>
     */
    public function all(): array
    {
        return $this->fieldTypes;
    }

    /**
     * Get all directive names mapped to their field types
     *
     * @return array<string, string>
     */
    public function getDirectiveMap(): array
    {
        return $this->directiveMap;
    }

    /**
     * Get the names of all registered field types
     *
     * @return array<string>
     */
    public function getFieldTypeNames(): array
    {
        return array_keys($this->fieldTypes);
    }
}
