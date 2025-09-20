<?php

namespace FrankenCms\Filament\Resources\CmsSettings\Pages;

use BackedEnum;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use FrankenCms\Filament\Resources\CmsSettings\Schemas\CmsSettingsSchema;
use FrankenCms\Settings\GeneralSettings;
use Illuminate\Contracts\Support\Htmlable;

class CmsSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;
    protected static string | BackedEnum | null $navigationIcon = Heroicon::AdjustmentsVertical;

    protected static ?string $description = 'Configure your site settings.';
    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('franken-cms::messages.settings.label');
    }

    public function getTitle(): string
    {
        return __('franken-cms::messages.settings.general.title');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('franken-cms::messages.settings.general.description');
    }

    public function form(Schema $schema): Schema
    {
        return CmsSettingsSchema::make($schema);
    }
}
