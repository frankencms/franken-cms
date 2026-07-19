<?php

namespace FrankenCms\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Override;

enum PermalinkTags: string implements HasDescription, HasLabel
{
    case YEAR = '%year%';
    case MONTHNUM = '%monthnum%';
    case DAY = '%day%';
    case HOUR = '%hour%';
    case MINUTE = '%minute%';
    case SECOND = '%second%';
    case POST_ID = '%post_id%';
    case POSTNAME = '%postname%';
    case CATEGORY = '%category%';
    case AUTHOR = '%author%';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::YEAR     => '%year%',
            self::MONTHNUM => '%monthnum%',
            self::DAY      => '%day%',
            self::HOUR     => '%hour%',
            self::MINUTE   => '%minute%',
            self::SECOND   => '%second%',
            self::POST_ID  => '%post_id%',
            self::POSTNAME => '%postname%',
            self::CATEGORY => '%category%',
            self::AUTHOR   => '%author%',

        };

    }

    #[Override]
    public function getDescription(): string
    {
        return match ($this) {
            self::YEAR     => 'The year of the post, four digits (e.g., 2025)',
            self::MONTHNUM => 'The numeric month of the year, with leading zeros (e.g., 01)',
            self::DAY      => 'The numeric day of the month, with leading zeros (e.g., 26)',
            self::HOUR     => 'The hour of the day, with leading zeros (e.g., 14)',
            self::MINUTE   => 'The minute of the hour, with leading zeros (e.g., 30)',
            self::SECOND   => 'The second of the minute, with leading zeros (e.g., 45)',
            self::POST_ID  => 'The unique ID of the post (e.g., 123)',
            self::POSTNAME => 'The sanitized post name or slug (e.g., sample-post)',
            self::CATEGORY => 'The category of the post (e.g., news)',
            self::AUTHOR   => 'The author of the post (e.g., admin)',
        };

    }
}
