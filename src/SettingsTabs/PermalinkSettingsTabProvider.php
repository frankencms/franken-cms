<?php

namespace FrankenCms\SettingsTabs;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Enums\PermalinkTags;
use FrankenCms\Rules\PermalinkContainsPostPlaceholder;
use FrankenCms\Settings\PermalinkSettings;
use Illuminate\Support\HtmlString;

class PermalinkSettingsTabProvider implements SettingsTabProviderInterface
{
    public function getTab(): Tab
    {
        return Tab::make('Permalinks')
            ->icon('heroicon-o-link')
            ->schema([
                Section::make(__('franken-cms::messages.settings.permalinks.title'))
                    ->description(__('franken-cms::messages.settings.permalinks.description'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Common Settings')
                            ->label('Common Settings')
                            ->columns(3)
                            ->columnSpanFull()
                            ->schema([
                                Html::make(new HtmlString(__('franken-cms::messages.settings.permalinks.form.placeholder.content')))
                                    ->key('common_settings_placeholder')
                                    ->columnSpanFull(),

                                Radio::make('permalink_structure')
                                    ->live()
                                    ->inlineLabel()
                                    ->label('Permalink Structure')
                                    ->options($this->enumOptions(PermalinkStructure::class))
                                    ->default(PermalinkStructure::POST_NAME->value)
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('custom_permalink_structure')
                                    ->label('Custom Structure')
                                    ->visible(function (Get $get) {
                                        return $get('permalink_structure') === PermalinkStructure::CUSTOM->value;
                                    })
                                    ->inlineLabel()
                                    ->rules(['required_if:permalink_structure,' . PermalinkStructure::CUSTOM->value, new PermalinkContainsPostPlaceholder])
                                    ->options($this->enumOptions(PermalinkTags::class))
                                    ->multiple()
                                    ->helperText(fn ($state) => implode('/', $state))
                                    ->columnSpan(2),

                                Fieldset::make('Optional')
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->schema([
                                        Html::make(new HtmlString(__('franken-cms::messages.settings.permalinks.form.optional_placeholder.content')))
                                            ->key('optional_placeholder')
                                            ->columnSpanFull(),

                                        TextInput::make('category_base_url')
                                            ->label('Category Base')
                                            ->inlineLabel()
                                            ->default('category')
                                            ->required()
                                            ->columnSpan(2),

                                        TextInput::make('tag_base_url')
                                            ->label('Tag Base')
                                            ->inlineLabel()
                                            ->default('tag')
                                            ->required()
                                            ->columnSpan(2),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function getSettingsClass(): string
    {
        return PermalinkSettings::class;
    }

    public function getOrder(): int
    {
        return 60;
    }

    public function getTabKey(): string
    {
        return 'permalinks';
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