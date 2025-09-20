<?php

namespace FrankenCms\SettingsTabs;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\TimeFormat;
use FrankenCms\Enums\UserRole;
use FrankenCms\Helpers\TimezoneHelper;
use FrankenCms\Settings\GeneralSettings;
use Illuminate\Support\HtmlString;

class GeneralSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make(__('franken-cms::messages.settings.general.title'))
            ->schema([
                Section::make(__('franken-cms::messages.settings.general.title'))
                    ->description(__('franken-cms::messages.settings.general.description'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->inlineLabel()
                            ->required()
                            ->label(__('franken-cms::messages.settings.general.form.title.label'))
                            ->columnSpan(2),
                        TextArea::make('tagline')
                            ->inlineLabel()
                            ->label(__('franken-cms::messages.settings.general.form.tagline.label'))
                            ->columnSpan(2)
                            ->helperText(__('franken-cms::messages.settings.general.form.tagline.helper')),

                        FileUpload::make('icon')
                            ->label(trans('franken-cms::messages.settings.general.form.icon.label'))
                            ->inlineLabel()
                            ->avatar()
                            ->columnSpan(2)
                            ->helperText(__('franken-cms::messages.settings.general.form.icon.helper')),

                        Toggle::make('membership')
                            ->inlineLabel()
                            ->label(__('franken-cms::messages.settings.general.form.membership.label'))
                            ->helperText(__('franken-cms::messages.settings.general.form.membership.helper'))
                            ->required()
                            ->columnSpan(2),

                        Select::make('new_user_default_role')
                            ->inlineLabel()
                            ->required()
                            ->label(__('franken-cms::messages.settings.general.form.default_user_role.label'))
                            ->options($this->enumOptions(UserRole::class))
                            ->selectablePlaceholder(false)
                            ->columnSpan(2),

                        Select::make('timezone')
                            ->inlineLabel()
                            ->label(__('franken-cms::messages.settings.general.form.timezone.label'))
                            ->helperText(__('franken-cms::messages.settings.general.form.timezone.helper'))
                            ->options(TimezoneHelper::getGroupedTimezones())
                            ->default('UTC+0')
                            ->required()
                            ->columnSpan(2),

                        Radio::make('date_format')
                            ->inlineLabel()
                            ->live()
                            ->label(__('franken-cms::messages.settings.general.form.date_format.label'))
                            ->options($this->enumOptions(DateFormat::class))
                            ->default(DateFormat::FULL_MONTH_DAY_YEAR)
                            ->helperText(function (Get $get, $state) {
                                $format = $state instanceof BackedEnum ? $state->value : $state;
                                if ($format === DateFormat::CUSTOM) {
                                    $custom = $get('custom_date_format');
                                    if (is_string($custom) && $custom !== '') {
                                        $format = $custom;
                                    }
                                }
                                return new HtmlString('<p><strong>Preview: </strong>' . now()->format((string) $format) . '</p>');
                            })
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('custom_date_format')
                            ->inlineLabel()
                            ->label(__('franken-cms::messages.settings.general.form.custom_date_format.label'))
                            ->columnSpan(2)
                            ->visible(fn (Get $get) => $get('date_format') === DateFormat::CUSTOM),

                        Radio::make('time_format')
                            ->inlineLabel()
                            ->live()
                            ->label(__('franken-cms::messages.settings.general.form.time_format.label'))
                            ->options($this->enumOptions(TimeFormat::class))
                            ->default(TimeFormat::HOURS_12_MINUTES_LOWERCASE->value)
                            ->helperText(function (Get $get, $state) {
                                $format = $state instanceof BackedEnum ? $state->value : $state;
                                if ($format === TimeFormat::CUSTOM->value) {
                                    $custom = $get('custom_time_format');
                                    if (is_string($custom) && $custom !== '') {
                                        $format = $custom;
                                    }
                                }
                                return new HtmlString('<p><strong>Preview: </strong>' . now()->format((string) $format) . '</p>');
                            })
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('custom_time_format')
                            ->inlineLabel()
                            ->label(__('franken-cms::messages.settings.general.form.custom_time_format.label'))
                            ->columnSpan(2)
                            ->visible(fn (Get $get) => $get('time_format') === TimeFormat::CUSTOM->value),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return GeneralSettings::class;
    }

    public function getOrder(): int
    {
        return 10;
    }

    public function getTabKey(): string
    {
        return 'general';
    }

    /**
     * Convert an enum class to options array with string values as keys and labels as values
     * which is needed to be compatible with spatie/laravel-settings.
     */
    private function enumOptions(string $enumClass): array
    {
        return collect($enumClass::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}