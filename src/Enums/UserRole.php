<?php

declare(strict_types=1);

namespace FrankenCms\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case SUBSCRIBER = 'subscriber';
    case CONTRIBUTOR = 'contributor';
    case AUTHOR = 'author';
    case EDITOR = 'editor';
    case ADMINISTRATOR = 'administrator';

    /**
     * Get a description of the user role.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SUBSCRIBER    => 'A user with limited access, mainly to view content.',
            self::CONTRIBUTOR   => 'A user who can write and manage their own posts but cannot publish them.',
            self::AUTHOR        => 'A user who can write, manage, and publish their own posts.',
            self::EDITOR        => 'A user who can manage and publish posts, including those of other users.',
            self::ADMINISTRATOR => 'A user with full access to all features and settings.',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SUBSCRIBER    => 'Subscriber',
            self::CONTRIBUTOR   => 'Contributor',
            self::AUTHOR        => 'Author',
            self::EDITOR        => 'Editor',
            self::ADMINISTRATOR => 'Administrator',
        };

    }

    /**
     * Check if the role has administrative privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMINISTRATOR;
    }
}
