<?php

namespace FrankenCms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \FrankenCms\FrankenCms
 */
class FrankenCms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \FrankenCms\FrankenCms::class;
    }
}
