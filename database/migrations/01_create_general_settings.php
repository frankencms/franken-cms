<?php

use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\TimeFormat;
use FrankenCms\Enums\UserRole;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // General Settings Group (cms_general)
        $this->migrator->add('cms_general.title', 'Franken CMS');
        $this->migrator->add('cms_general.icon');
        $this->migrator->add('cms_general.membership', false);
        $this->migrator->add('cms_general.new_user_default_role', UserRole::SUBSCRIBER->value);
        $this->migrator->add('cms_general.language');
        $this->migrator->add('cms_general.timezone', 'UTC');
        $this->migrator->add('cms_general.date_format', DateFormat::FULL_MONTH_DAY_YEAR->value);
        $this->migrator->add('cms_general.custom_date_format');
        $this->migrator->add('cms_general.time_format', TimeFormat::HOURS_12_MINUTES_LOWERCASE->value);
        $this->migrator->add('cms_general.custom_time_format');
    }
};