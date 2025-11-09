<?php

namespace FrankenCms\Contracts;

interface FieldTypeInterface
{
    /**
     * Get the unique identifier for this field type
     */
    public function getName(): string;

    /**
     * Get the directive names that should be registered for this field type
     * Returns an array of directive suffixes (e.g., ['Text', 'TextField'])
     */
    public function getDirectiveNames(): array;

    /**
     * Get the Filament component class for this field type
     */
    public function getFilamentComponentClass(): string;

    /**
     * Get the renderer class for this field type
     */
    public function getRendererClass(): string;

    /**
     * Check if this field type supports options
     */
    public function supportsOptions(): bool;

    /**
     * Get whether this is a block directive (has opening and closing tags)
     */
    public function isBlockDirective(): bool;

    /**
     * Get additional configuration for this field type
     */
    public function getConfig(): array;
}
