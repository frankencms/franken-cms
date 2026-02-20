<?php

namespace FrankenCms\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum LinkTargets: string implements HasLabel
{
    case _SELF = '_self';
    case _BLANK = '_blank';
    case _PARENT = '_parent';
    case _TOP = '_top';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::_SELF   => __('Same Window'),
            self::_BLANK  => __('New Window'),
            self::_PARENT => __('Parent Frame'),
            self::_TOP    => __('Top Frame'),
        };

    }
}
