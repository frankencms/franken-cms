<?php

declare(strict_types=1);

namespace FrankenCms\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum PermalinkStructure: string implements HasDescription, HasLabel
{
    case PLAIN = '?p=123';                          // Example: ?p=123
    case DAY_AND_NAME = '/%year%/%monthnum%/%day%/%postname%/'; // Example: /2025/01/26/sample-post/
    case MONTH_AND_NAME = '/%year%/%monthnum%/%postname%/';      // Example: /2025/01/sample-post/
    case NUMERIC = '/archives/%post_id%';           // Example: /archives/123
    case POST_NAME = '/%postname%/';                // Example: /sample-post/
    case CUSTOM = 'custom';                               // Custom structure defined dynamically

    /**
     * Get the available permalink tags that can be used in custom structures.
     */
    public static function getPermalinkTags(): array
    {
        return [
            '%year%'     => 'The year of the post, four digits (e.g., 2025)',
            '%monthnum%' => 'The numeric month of the year, with leading zeros (e.g., 01)',
            '%day%'      => 'The numeric day of the month, with leading zeros (e.g., 26)',
            '%hour%'     => 'The hour of the day, with leading zeros (e.g., 14)',
            '%minute%'   => 'The minute of the hour, with leading zeros (e.g., 30)',
            '%second%'   => 'The second of the minute, with leading zeros (e.g., 45)',
            '%post_id%'  => 'The unique ID of the post (e.g., 123)',
            '%postname%' => 'The sanitized post name or slug (e.g., sample-post)',
            '%category%' => 'The category of the post (e.g., news)',
            '%author%'   => 'The author of the post (e.g., admin)',
        ];
    }

    /**
     * Get an example URL based on the permalink structure.
     * Replace placeholders with example values for demonstration purposes.
     */
    public function getExample(): string
    {
        return match ($this) {
            self::PLAIN          => '/?p=123',
            self::DAY_AND_NAME   => '/2025/01/26/sample-post/',
            self::MONTH_AND_NAME => '/2025/01/sample-post/',
            self::NUMERIC        => '/archives/123',
            self::POST_NAME      => '/sample-post/',
            self::CUSTOM         => '/your-custom-structure/',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PLAIN          => 'Plain',
            self::DAY_AND_NAME   => 'Day and Name',
            self::MONTH_AND_NAME => 'Month and Name',
            self::NUMERIC        => 'Numeric',
            self::POST_NAME      => 'Post Name',
            self::CUSTOM         => 'Custom',
        };

    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PLAIN          => url('/?p=123'),
            self::DAY_AND_NAME   => url('/2025/01/26/sample-post/'),
            self::MONTH_AND_NAME => url('/2025/01/sample-post/'),
            self::NUMERIC        => url('/archives/123'),
            self::POST_NAME      => url('/sample-post/'),
            self::CUSTOM         => 'Custom structure defined dynamically',
        };
    }
}
