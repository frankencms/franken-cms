<?php

namespace FrankenCms\ValueObjects;

use FrankenCms\Contracts\FieldTypeInterface;

class FieldType implements FieldTypeInterface
{
    public function __construct(
        protected string $name,
        protected array $directiveNames,
        protected string $filamentComponentClass,
        protected string $rendererClass,
        protected bool $supportsOptions = true,
        protected bool $isBlockDirective = false,
        protected array $config = []
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDirectiveNames(): array
    {
        return $this->directiveNames;
    }

    public function getFilamentComponentClass(): string
    {
        return $this->filamentComponentClass;
    }

    public function getRendererClass(): string
    {
        return $this->rendererClass;
    }

    public function supportsOptions(): bool
    {
        return $this->supportsOptions;
    }

    public function isBlockDirective(): bool
    {
        return $this->isBlockDirective;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Create a new FieldType instance using static factory method
     */
    public static function make(
        string $name,
        array $directiveNames,
        string $filamentComponentClass,
        string $rendererClass,
        bool $supportsOptions = true,
        bool $isBlockDirective = false,
        array $config = []
    ): static {
        return new static(
            $name,
            $directiveNames,
            $filamentComponentClass,
            $rendererClass,
            $supportsOptions,
            $isBlockDirective,
            $config
        );
    }
}
