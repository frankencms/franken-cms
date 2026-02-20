<?php

namespace FrankenCms\Contracts;

interface DirectiveProviderInterface
{
    /**
     * Register the Blade directives for this field type
     */
    public function register(): void;

    /**
     * Get the field type definition for this provider
     */
    public function getFieldType(): FieldTypeInterface;
}
