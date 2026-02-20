<?php

use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\TimeFormat;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // General Settings Group (franken_cms_general)
        $this->migrator->add('franken_cms_general.title', 'Franken CMS');
        $this->migrator->add('franken_cms_general.icon');
        $this->migrator->add('franken_cms_general.membership', false);
        $this->migrator->add('franken_cms_general.language');
        $this->migrator->add('franken_cms_general.timezone', 'UTC');
        $this->migrator->add('franken_cms_general.date_format', DateFormat::FULL_MONTH_DAY_YEAR->value);
        $this->migrator->add('franken_cms_general.custom_date_format');
        $this->migrator->add('franken_cms_general.time_format', TimeFormat::HOURS_12_MINUTES_LOWERCASE->value);
        $this->migrator->add('franken_cms_general.custom_time_format');
    }
};
