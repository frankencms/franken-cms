<?php

namespace FrankenCms\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PostStatus: string implements HasDescription, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PRIVATE = 'private';
    case SCHEDULED = 'scheduled';
    case PUBLISH = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::PENDING   => 'Pending',
            self::PRIVATE   => 'Private',
            self::SCHEDULED => 'Scheduled',
            self::PUBLISH   => 'Published',
        };

    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT     => 'Not ready to publish.',
            self::PENDING   => 'Waiting for review before publishing.',
            self::PRIVATE   => 'Only visible to administrators and editors.',
            self::SCHEDULED => 'Published automatically at a future date.',
            self::PUBLISH   => 'Visible to everyone.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING   => 'heroicon-o-clock',
            self::PRIVATE   => 'heroicon-o-eye-off',
            self::SCHEDULED => 'heroicon-o-calendar',
            self::PUBLISH   => 'heroicon-o-check-circle',
            default         => 'heroicon-o-pencil',
        };

    }
}
