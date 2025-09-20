<?php

namespace FrankenCms\Settings;

use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\TimeFormat;
use FrankenCms\Enums\UserRole;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $title;
    public ?string $tagline = null;
    public ?string $icon = null;
    public bool $membership = false;
    public ?string $new_user_default_role = UserRole::SUBSCRIBER->value;
    public ?string $language = null;
    public ?string $timezone = 'UTC+0';
    public ?string $date_format = DateFormat::FULL_MONTH_DAY_YEAR->value;
    public ?string $custom_date_format = null;
    public ?string $time_format = TimeFormat::HOURS_12_MINUTES_LOWERCASE->value;
    public ?string $custom_time_format = null;

    public static function group(): string
    {
        return 'cms_general';
    }
}
